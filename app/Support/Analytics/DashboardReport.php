<?php

namespace App\Support\Analytics;

use App\Enums\InquiryChannel;
use App\Enums\WatchStatus;
use App\Models\Inquiry;
use App\Models\PageView;
use App\Models\SearchQuery;
use App\Models\Watch;
use App\Support\SiteSettings;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;
use Illuminate\Support\Str;

/**
 * Store performance for a period of days, compared with the period before it.
 */
class DashboardReport
{
    /**
     * Periods the dashboard can be viewed over, in days.
     *
     * @var list<int>
     */
    public const PERIODS = [7, 30, 90];

    /**
     * A watch needs at least this many views before its conversion rate means anything.
     */
    private const MEANINGFUL_VIEWS = 10;

    /**
     * Available watches listed for longer than this are considered slow to sell.
     */
    private const SLOW_MOVER_DAYS = 60;

    private CarbonImmutable $start;

    private CarbonImmutable $previousStart;

    public function __construct(
        private int $days,
        private SiteSettings $settings,
    ) {
        $this->start = CarbonImmutable::today()->subDays($days - 1);
        $this->previousStart = $this->start->subDays($days);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $kpis = $this->kpis();
        $sources = $this->sources();
        $devices = $this->devices();
        $watches = $this->watchPerformance();
        $searches = $this->searches();
        $inventory = $this->inventory();

        return [
            'period' => [
                'days' => $this->days,
                'start' => $this->start->toDateString(),
                'end' => CarbonImmutable::today()->toDateString(),
                'options' => self::PERIODS,
            ],
            'kpis' => $kpis,
            'series' => $this->series(),
            'sources' => $sources,
            'devices' => $devices,
            'channels' => $this->channels(),
            'watches' => $watches,
            'searches' => $searches,
            'inventory' => $inventory,
            'attention' => $this->attention($watches),
            'insights' => $this->insights($kpis, $sources, $devices, $watches, $searches, $inventory),
            'recentInquiries' => $this->recentInquiries(),
        ];
    }

    /**
     * Headline numbers for this period and the one before.
     *
     * @return array<string, array{current: int|float, previous: int|float}>
     */
    private function kpis(): array
    {
        $visits = $this->compare(fn (CarbonImmutable $from, CarbonImmutable $to): int => $this->visits($from, $to));
        $watchViews = $this->compare(fn (CarbonImmutable $from, CarbonImmutable $to): int => PageView::query()
            ->whereNotNull('watch_id')
            ->whereBetween('created_at', [$from, $to])
            ->count());
        $inquiries = $this->compare(fn (CarbonImmutable $from, CarbonImmutable $to): int => Inquiry::query()
            ->whereBetween('created_at', [$from, $to])
            ->count());

        return [
            'visits' => $visits,
            'watchViews' => $watchViews,
            'inquiries' => $inquiries,
            'inquiryRate' => [
                'current' => $this->rate($inquiries['current'], $visits['current']),
                'previous' => $this->rate($inquiries['previous'], $visits['previous']),
            ],
        ];
    }

    /**
     * Daily visits, watch views and inquiries, including days with none.
     *
     * @return list<array{date: string, visits: int, watchViews: int, inquiries: int}>
     */
    private function series(): array
    {
        $visits = DB::query()
            ->fromSub($this->dailyVisitorsQuery($this->start, now()), 'daily_visitors')
            ->selectRaw('day, count(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $watchViews = PageView::query()
            ->whereNotNull('watch_id')
            ->where('created_at', '>=', $this->start)
            ->selectRaw('date(created_at) as day, count(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $inquiries = Inquiry::query()
            ->where('created_at', '>=', $this->start)
            ->selectRaw('date(created_at) as day, count(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        return array_map(function (int $offset) use ($visits, $watchViews, $inquiries): array {
            $date = $this->start->addDays($offset)->toDateString();

            return [
                'date' => $date,
                'visits' => (int) ($visits[$date] ?? 0),
                'watchViews' => (int) ($watchViews[$date] ?? 0),
                'inquiries' => (int) ($inquiries[$date] ?? 0),
            ];
        }, range(0, $this->days - 1));
    }

    /**
     * @return list<array{name: string, visits: int, share: float}>
     */
    private function sources(): array
    {
        return $this->breakdown('source');
    }

    /**
     * @return list<array{name: string, visits: int, share: float}>
     */
    private function devices(): array
    {
        return array_map(
            fn (array $row): array => [...$row, 'name' => Str::ucfirst($row['name'])],
            $this->breakdown('device'),
        );
    }

    /**
     * @return list<array{channel: string, label: string, total: int, share: float}>
     */
    private function channels(): array
    {
        $totals = Inquiry::query()
            ->where('created_at', '>=', $this->start)
            ->selectRaw('channel, count(*) as total')
            ->groupBy('channel')
            ->pluck('total', 'channel');

        $sum = (int) $totals->sum();

        return array_map(fn (InquiryChannel $channel): array => [
            'channel' => $channel->value,
            'label' => $channel->label(),
            'total' => (int) ($totals[$channel->value] ?? 0),
            'share' => $this->rate((int) ($totals[$channel->value] ?? 0), $sum),
        ], InquiryChannel::cases());
    }

    /**
     * Views, inquiries and conversion for every watch that saw activity in the period.
     *
     * @return list<array<string, mixed>>
     */
    private function watchPerformance(): array
    {
        $watches = Watch::query()
            ->with(['brand', 'primaryImage'])
            ->withCount([
                'pageViews as period_views' => fn (Builder $query) => $query->where('created_at', '>=', $this->start),
                'inquiries as period_inquiries' => fn (Builder $query) => $query->where('created_at', '>=', $this->start),
            ])
            ->get()
            ->filter(fn (Watch $watch): bool => $watch->getAttribute('period_views') > 0 || $watch->getAttribute('period_inquiries') > 0)
            ->sortByDesc(fn (Watch $watch): array => [$watch->getAttribute('period_inquiries'), $watch->getAttribute('period_views')]);

        return array_values($watches->map(fn (Watch $watch): array => [
            'id' => $watch->id,
            'name' => $watch->name,
            'brand' => $watch->brand->name,
            'slug' => $watch->slug,
            'imageUrl' => $watch->primaryImage?->url,
            'price' => $watch->price !== null ? (float) $watch->price : null,
            'status' => $watch->status->value,
            'statusLabel' => $watch->status->label(),
            'views' => (int) $watch->getAttribute('period_views'),
            'inquiries' => (int) $watch->getAttribute('period_inquiries'),
            'conversion' => $this->rate((int) $watch->getAttribute('period_inquiries'), (int) $watch->getAttribute('period_views')),
        ])->all());
    }

    /**
     * What visitors searched for, and what they could not find.
     *
     * @return array{top: list<array{term: string, count: int, results: int}>, unmet: list<array{term: string, count: int}>, total: int}
     */
    private function searches(): array
    {
        $rows = SearchQuery::query()
            ->where('created_at', '>=', $this->start)
            ->selectRaw('term, count(*) as searches, min(results_count) as fewest_results')
            ->groupBy('term')
            ->orderByDesc('searches')
            ->get();

        return [
            'total' => (int) $rows->sum('searches'),
            'top' => array_values($rows->take(8)->map(fn (SearchQuery $row): array => [
                'term' => $row->term,
                'count' => (int) $row->getAttribute('searches'),
                'results' => (int) $row->getAttribute('fewest_results'),
            ])->all()),
            'unmet' => array_values($rows
                ->filter(fn (SearchQuery $row): bool => (int) $row->getAttribute('fewest_results') === 0)
                ->take(8)
                ->map(fn (SearchQuery $row): array => [
                    'term' => $row->term,
                    'count' => (int) $row->getAttribute('searches'),
                ])->all()),
        ];
    }

    /**
     * Stock position and how quickly watches sell.
     *
     * @return array<string, int|float|null>
     */
    private function inventory(): array
    {
        $statusCounts = Watch::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $available = Watch::query()->where('status', WatchStatus::Available)->get(['id', 'price', 'created_at']);
        $soldInPeriod = Watch::query()->where('sold_at', '>=', $this->start)->get(['id', 'price', 'created_at', 'sold_at']);
        $soldThisYear = Watch::query()->where('sold_at', '>=', now()->subYear())->get(['id', 'created_at', 'sold_at']);

        return [
            'total' => (int) $statusCounts->sum(),
            'available' => (int) ($statusCounts[WatchStatus::Available->value] ?? 0),
            'reserved' => (int) ($statusCounts[WatchStatus::Reserved->value] ?? 0),
            'sold' => (int) ($statusCounts[WatchStatus::Sold->value] ?? 0),
            'unpublished' => Watch::query()->where('is_published', false)->count(),
            'availableValue' => (float) $available->sum('price'),
            'soldInPeriod' => $soldInPeriod->count(),
            'soldValueInPeriod' => (float) $soldInPeriod->sum('price'),
            'averageDaysToSell' => $this->averageDays($soldThisYear, fn (Watch $watch): ?CarbonImmutable => $watch->sold_at?->toImmutable()),
            'averageDaysListed' => $this->averageDays($available, fn (): CarbonImmutable => CarbonImmutable::now()),
        ];
    }

    /**
     * Listings the owner should look at, most important first.
     *
     * @param  list<array<string, mixed>>  $performance
     * @return list<array{type: string, title: string, detail: string, watchId: int|null}>
     */
    private function attention(array $performance): array
    {
        $items = [];

        foreach ($performance as $watch) {
            if ($watch['status'] === WatchStatus::Available->value && $watch['views'] >= self::MEANINGFUL_VIEWS && $watch['inquiries'] === 0) {
                $items[] = [
                    'type' => 'no_inquiries',
                    'title' => "{$watch['brand']} {$watch['name']}",
                    'detail' => "{$watch['views']} views and no inquiries. Review the price, photos or description.",
                    'watchId' => $watch['id'],
                ];
            }
        }

        Watch::query()->doesntHave('images')->with('brand')->get()->each(function (Watch $watch) use (&$items): void {
            $items[] = [
                'type' => 'no_photos',
                'title' => "{$watch->brand->name} {$watch->name}",
                'detail' => 'Has no photos. Listings without photos rarely get inquiries.',
                'watchId' => $watch->id,
            ];
        });

        $viewedRecently = collect($performance)->pluck('views', 'id');

        Watch::query()
            ->published()
            ->where('status', WatchStatus::Available)
            ->where('created_at', '<=', now()->subDays(self::SLOW_MOVER_DAYS))
            ->with('brand')
            ->get()
            ->filter(fn (Watch $watch): bool => ($viewedRecently[$watch->id] ?? 0) < 5)
            ->each(function (Watch $watch) use (&$items): void {
                $items[] = [
                    'type' => 'slow_mover',
                    'title' => "{$watch->brand->name} {$watch->name}",
                    'detail' => 'Listed for '.(int) $watch->created_at?->diffInDays(now()).' days with very few recent views. Consider featuring it or adjusting the price.',
                    'watchId' => $watch->id,
                ];
            });

        $hidden = Watch::query()->where('is_published', false)->count();

        if ($hidden > 0) {
            $items[] = [
                'type' => 'hidden',
                'title' => Str::plural('watch', $hidden, prependCount: true).' hidden from the store',
                'detail' => 'Publish them when they are ready so visitors can find them.',
                'watchId' => null,
            ];
        }

        return array_slice($items, 0, 8);
    }

    /**
     * Plain language observations built from the numbers above.
     *
     * @param  array<string, array{current: int|float, previous: int|float}>  $kpis
     * @param  list<array{name: string, visits: int, share: float}>  $sources
     * @param  list<array{name: string, visits: int, share: float}>  $devices
     * @param  list<array<string, mixed>>  $watches
     * @param  array{top: list<array{term: string, count: int, results: int}>, unmet: list<array{term: string, count: int}>, total: int}  $searches
     * @param  array<string, int|float|null>  $inventory
     * @return list<array{tone: 'positive'|'negative'|'neutral', text: string}>
     */
    private function insights(array $kpis, array $sources, array $devices, array $watches, array $searches, array $inventory): array
    {
        $insights = [];
        $period = "the previous {$this->days} days";

        if ($kpis['visits']['current'] === 0) {
            return [[
                'tone' => 'neutral',
                'text' => 'No visits recorded in this period yet. Share your watches on social media or with past clients to start bringing people in.',
            ]];
        }

        $visitChange = $this->change($kpis['visits']['current'], $kpis['visits']['previous']);

        if ($visitChange !== null && abs($visitChange) >= 5) {
            $insights[] = [
                'tone' => $visitChange > 0 ? 'positive' : 'negative',
                'text' => 'Visits are '.($visitChange > 0 ? 'up' : 'down').' '.abs($visitChange)."% compared with {$period}.",
            ];
        }

        $inquiryChange = $this->change($kpis['inquiries']['current'], $kpis['inquiries']['previous']);

        if ($inquiryChange !== null && abs($inquiryChange) >= 5) {
            $insights[] = [
                'tone' => $inquiryChange > 0 ? 'positive' : 'negative',
                'text' => 'Inquiries are '.($inquiryChange > 0 ? 'up' : 'down').' '.abs($inquiryChange)."% compared with {$period}.",
            ];
        }

        if ($sources !== [] && $sources[0]['name'] !== 'Direct') {
            $insights[] = [
                'tone' => 'neutral',
                'text' => "{$sources[0]['name']} is your biggest source of visitors, bringing {$sources[0]['share']}% of visits.",
            ];
        }

        $converting = collect($watches)->filter(fn (array $watch): bool => $watch['status'] !== WatchStatus::Sold->value && $watch['views'] >= self::MEANINGFUL_VIEWS && $watch['inquiries'] > 0)->sortByDesc('conversion')->first();

        if ($converting) {
            $insights[] = [
                'tone' => 'positive',
                'text' => "The {$converting['brand']} {$converting['name']} turns {$converting['conversion']}% of its viewers into inquiries, your strongest listing.",
            ];
        }

        $overlooked = collect($watches)->filter(fn (array $watch): bool => $watch['status'] === WatchStatus::Available->value && $watch['views'] >= self::MEANINGFUL_VIEWS && $watch['inquiries'] === 0)->sortByDesc('views')->first();

        if ($overlooked) {
            $insights[] = [
                'tone' => 'negative',
                'text' => "The {$overlooked['brand']} {$overlooked['name']} had {$overlooked['views']} views but no inquiries. The price or photos may be holding it back.",
            ];
        }

        if ($searches['unmet'] !== []) {
            $unmet = $searches['unmet'][0];
            $insights[] = [
                'tone' => 'neutral',
                'text' => "Visitors searched for \"{$unmet['term']}\" ".Str::plural('time', $unmet['count'], prependCount: true).' and found nothing. That may be worth sourcing.',
            ];
        }

        $mobile = collect($devices)->firstWhere('name', 'Mobile');

        if ($mobile && $mobile['share'] >= 50) {
            $insights[] = [
                'tone' => 'neutral',
                'text' => "{$mobile['share']}% of visits are on phones, so check new listings on a phone before publishing.",
            ];
        }

        if ($inventory['soldInPeriod'] > 0) {
            $insights[] = [
                'tone' => 'positive',
                'text' => Str::plural('watch', (int) $inventory['soldInPeriod'], prependCount: true).' sold in this period, worth '
                    .Number::currency((float) $inventory['soldValueInPeriod'], $this->settings->currency(), precision: 0).' at list price.',
            ];
        }

        return array_slice($insights, 0, 6);
    }

    /**
     * @return list<array{id: int, channel: string, watch: array{id: int, name: string, brand: string}|null, created_at: string|null}>
     */
    private function recentInquiries(): array
    {
        return array_values(Inquiry::query()
            ->with('watch.brand')
            ->latest()
            ->take(8)
            ->get()
            ->map(fn (Inquiry $inquiry): array => [
                'id' => $inquiry->id,
                'channel' => $inquiry->channel->label(),
                'watch' => $inquiry->watch ? [
                    'id' => $inquiry->watch->id,
                    'name' => $inquiry->watch->name,
                    'brand' => $inquiry->watch->brand->name,
                ] : null,
                'created_at' => $inquiry->created_at?->toIso8601String(),
            ])
            ->all());
    }

    /**
     * A visit is one visitor on one day, since the visitor hash rotates daily.
     */
    private function visits(CarbonImmutable $from, CarbonImmutable $to): int
    {
        return DB::query()->fromSub($this->dailyVisitorsQuery($from, $to), 'daily_visitors')->count();
    }

    /**
     * @return Builder<PageView>
     */
    private function dailyVisitorsQuery(CarbonImmutable $from, \DateTimeInterface $to): Builder
    {
        return PageView::query()
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('visitor_hash, date(created_at) as day')
            ->groupBy('visitor_hash', 'day');
    }

    /**
     * Visits grouped by a column, largest first.
     *
     * @param  'source'|'device'  $column
     * @return list<array{name: string, visits: int, share: float}>
     */
    private function breakdown(string $column): array
    {
        $rows = PageView::query()
            ->where('created_at', '>=', $this->start)
            ->selectRaw(match ($column) {
                'source' => 'source as name, count(distinct visitor_hash) as visits',
                'device' => 'device as name, count(distinct visitor_hash) as visits',
            })
            ->groupBy($column)
            ->orderByDesc('visits')
            ->toBase()
            ->get();

        $total = (int) $rows->sum('visits');

        return array_values($rows->map(fn (object $row): array => [
            'name' => (string) $row->name,
            'visits' => (int) $row->visits,
            'share' => $this->rate((int) $row->visits, $total),
        ])->all());
    }

    /**
     * Run a measurement for this period and the previous one.
     *
     * @param  callable(CarbonImmutable, CarbonImmutable): int  $measure
     * @return array{current: int, previous: int}
     */
    private function compare(callable $measure): array
    {
        return [
            'current' => $measure($this->start, CarbonImmutable::now()),
            'previous' => $measure($this->previousStart, $this->start->subSecond()),
        ];
    }

    private function rate(int $part, int $whole): float
    {
        return $whole > 0 ? round($part / $whole * 100, 1) : 0.0;
    }

    private function change(int|float $current, int|float $previous): ?int
    {
        return $previous > 0 ? (int) round(($current - $previous) / $previous * 100) : null;
    }

    /**
     * @param  Collection<int, Watch>  $watches
     * @param  callable(Watch): ?CarbonImmutable  $until
     */
    private function averageDays(Collection $watches, callable $until): ?int
    {
        $days = $watches
            ->map(fn (Watch $watch): ?float => ($end = $until($watch)) && $watch->created_at ? $watch->created_at->diffInDays($end) : null)
            ->filter(fn (?float $days): bool => $days !== null);

        return $days->isEmpty() ? null : (int) round($days->avg());
    }
}
