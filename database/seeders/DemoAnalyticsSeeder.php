<?php

namespace Database\Seeders;

use App\Enums\InquiryChannel;
use App\Models\Inquiry;
use App\Models\PageView;
use App\Models\SearchQuery;
use App\Models\Watch;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Simulated traffic for previewing the dashboard. It is not part of the default
 * seeders and must never run against a live store:
 *
 * php artisan db:seed --class=DemoAnalyticsSeeder
 */
class DemoAnalyticsSeeder extends Seeder
{
    private const DAYS = 120;

    /**
     * @var array<string, int>
     */
    private const SOURCES = ['Direct' => 30, 'Google' => 30, 'Instagram' => 20, 'Facebook' => 7, 'WhatsApp' => 6, 'Other websites' => 7];

    /**
     * @var array<string, int>
     */
    private const DEVICES = ['mobile' => 64, 'desktop' => 31, 'tablet' => 5];

    /**
     * @var list<string>
     */
    private const SEARCHES = ['rolex', 'submariner', 'omega', 'datejust', 'chronograph', 'patek philippe', 'cartier tank', 'audemars piguet', 'tudor black bay', 'explorer'];

    public function run(): void
    {
        $watches = Watch::query()->published()->get(['id', 'slug'])->keyBy('id');

        if ($watches->isEmpty() || PageView::query()->exists()) {
            return;
        }

        $popularity = $watches->mapWithKeys(fn (Watch $watch): array => [$watch->id => fake()->numberBetween(1, 10)]);
        $views = [];
        $inquiries = [];
        $searches = [];

        foreach (range(self::DAYS, 0) as $daysAgo) {
            $day = now()->subDays($daysAgo)->startOfDay();
            $growth = 1 + (self::DAYS - $daysAgo) / self::DAYS;
            $weekend = $day->isWeekend() ? 1.3 : 1;

            foreach (range(1, (int) round(fake()->numberBetween(10, 22) * $growth * $weekend)) as $ignored) {
                $this->simulateVisit($day, $watches, $popularity, $views, $inquiries, $searches);
            }
        }

        foreach (array_chunk($views, 500) as $chunk) {
            PageView::query()->insert($chunk);
        }

        foreach (array_chunk($inquiries, 500) as $chunk) {
            Inquiry::query()->insert($chunk);
        }

        foreach (array_chunk($searches, 500) as $chunk) {
            SearchQuery::query()->insert($chunk);
        }

        DB::table('watches')->update([
            'views_count' => DB::raw('(select count(*) from page_views where page_views.watch_id = watches.id)'),
        ]);
    }

    /**
     * @param  Collection<int, Watch>  $watches
     * @param  Collection<int, int>  $popularity
     * @param  list<array<string, mixed>>  $views
     * @param  list<array<string, mixed>>  $inquiries
     * @param  list<array<string, mixed>>  $searches
     */
    private function simulateVisit(CarbonInterface $day, Collection $watches, Collection $popularity, array &$views, array &$inquiries, array &$searches): void
    {
        $visitor = hash('sha256', fake()->uuid());
        $source = $this->weighted(self::SOURCES);
        $device = $this->weighted(self::DEVICES);
        $time = $day->copy()->addMinutes(fake()->numberBetween(420, 1380));
        $pages = [['/', null]];

        foreach (range(1, fake()->numberBetween(1, 4)) as $ignored) {
            $watch = $watches->get($this->weighted($popularity->all()));

            if ($watch !== null) {
                $pages[] = ['/watches/'.$watch->slug, $watch->id];
            }
        }

        if (fake()->boolean(12)) {
            $pages[] = [fake()->randomElement(['/about', '/contact']), null];
        }

        foreach (collect($pages)->unique(fn (array $page): string => $page[0]) as [$path, $watchId]) {
            $views[] = [
                'watch_id' => $watchId,
                'path' => $path,
                'visitor_hash' => $visitor,
                'source' => $source,
                'referrer_host' => ['Google' => 'google.com', 'Instagram' => 'instagram.com', 'Facebook' => 'facebook.com', 'WhatsApp' => 'wa.me', 'Other websites' => 'watchuseek.com'][$source] ?? null,
                'device' => $device,
                'created_at' => $time = $time->copy()->addSeconds(fake()->numberBetween(20, 240)),
            ];
        }

        $viewedWatch = collect($pages)->pluck(1)->filter()->last();

        if ($viewedWatch && fake()->boolean(7)) {
            $inquiries[] = [
                'watch_id' => $viewedWatch,
                'channel' => fake()->randomElement([InquiryChannel::Whatsapp, InquiryChannel::Whatsapp, InquiryChannel::Email, InquiryChannel::Chatwoot])->value,
                'created_at' => $time,
                'updated_at' => $time,
            ];
        }

        if (fake()->boolean(14)) {
            $term = fake()->randomElement(self::SEARCHES);

            $searches[] = [
                'term' => $term,
                'results_count' => Watch::query()->published()->filter(['search' => $term])->count(),
                'visitor_hash' => $visitor,
                'created_at' => $time,
            ];
        }
    }

    /**
     * @template TKey of array-key
     *
     * @param  array<TKey, int>  $weights
     * @return TKey
     */
    private function weighted(array $weights): string|int
    {
        $pick = fake()->numberBetween(1, array_sum($weights));

        foreach ($weights as $value => $weight) {
            $pick -= $weight;

            if ($pick <= 0) {
                return $value;
            }
        }

        return array_key_first($weights);
    }
}
