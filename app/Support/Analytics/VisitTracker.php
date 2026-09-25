<?php

namespace App\Support\Analytics;

use App\Models\PageView;
use App\Models\SearchQuery;
use App\Models\Watch;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Records storefront traffic from real visitors without cookies or personal data.
 */
class VisitTracker
{
    /**
     * A repeat view of the same page by the same visitor within this window is not counted again.
     */
    private const REPEAT_VIEW_MINUTES = 30;

    /**
     * Searches typed in quick succession are treated as one search.
     */
    private const SEARCH_REFINEMENT_MINUTES = 2;

    private const BOT_PATTERN = '/bot|crawl|spider|slurp|mediapartners|facebookexternalhit|whatsapp|telegram|embedly|preview|headless|lighthouse|pingdom|uptime|monitor|curl|wget|python|http-client|axios|go-http|okhttp|java\//i';

    /**
     * @var array<string, list<string>>
     */
    private const SOURCES = [
        'Google' => ['google.'],
        'Bing' => ['bing.com'],
        'DuckDuckGo' => ['duckduckgo.com'],
        'Yahoo' => ['yahoo.'],
        'Instagram' => ['instagram.com'],
        'Facebook' => ['facebook.com', 'fb.com', 'fb.me'],
        'X' => ['t.co', 'twitter.com', 'x.com'],
        'TikTok' => ['tiktok.com'],
        'YouTube' => ['youtube.com', 'youtu.be'],
        'Pinterest' => ['pinterest.'],
        'Reddit' => ['reddit.com'],
        'LinkedIn' => ['linkedin.com', 'lnkd.in'],
        'WhatsApp' => ['whatsapp.com', 'wa.me'],
    ];

    /**
     * Only count human visitors browsing the storefront, never staff or machines.
     */
    public function shouldTrack(Request $request): bool
    {
        return $request->isMethod('GET')
            && ! $request->prefetch()
            && ! $request->hasHeader('X-Inertia-Partial-Data')
            && ! $request->user()?->isAdmin()
            && ! $this->isBot($request);
    }

    public function recordPageView(Request $request, ?Watch $watch = null): void
    {
        $visitorHash = $this->visitorHash($request);
        $path = '/'.ltrim($request->path(), '/');

        $alreadyCounted = PageView::query()
            ->where('visitor_hash', $visitorHash)
            ->where('path', $path)
            ->where('created_at', '>=', now()->subMinutes(self::REPEAT_VIEW_MINUTES))
            ->exists();

        if ($alreadyCounted) {
            return;
        }

        [$source, $referrerHost] = $this->source($request);

        PageView::query()->create([
            'watch_id' => $watch?->id,
            'path' => Str::limit($path, 250, ''),
            'visitor_hash' => $visitorHash,
            'source' => $source,
            'referrer_host' => $referrerHost,
            'device' => $this->device((string) $request->userAgent()),
        ]);

        $watch?->incrementQuietly('views_count');
    }

    /**
     * Record a catalogue search, replacing the partial searches typed just before it.
     */
    public function recordSearch(Request $request, string $term, int $resultsCount): void
    {
        $term = Str::lower(Str::squish($term));

        if (mb_strlen($term) < 2) {
            return;
        }

        $visitorHash = $this->visitorHash($request);

        SearchQuery::query()
            ->where('visitor_hash', $visitorHash)
            ->where('created_at', '>=', now()->subMinutes(self::SEARCH_REFINEMENT_MINUTES))
            ->get(['id', 'term'])
            ->filter(fn (SearchQuery $previous): bool => str_starts_with($term, $previous->term) || str_starts_with($previous->term, $term))
            ->each->delete();

        SearchQuery::query()->create([
            'term' => Str::limit($term, 80, ''),
            'results_count' => $resultsCount,
            'visitor_hash' => $visitorHash,
        ]);
    }

    private function isBot(Request $request): bool
    {
        $userAgent = (string) $request->userAgent();

        return $userAgent === '' || preg_match(self::BOT_PATTERN, $userAgent) === 1;
    }

    /**
     * An anonymous identifier that changes daily, so visitors can be counted but not followed.
     */
    private function visitorHash(Request $request): string
    {
        return hash('sha256', implode('|', [
            $request->ip(),
            $request->userAgent(),
            now()->toDateString(),
            config('app.key'),
        ]));
    }

    /**
     * Work out where the visitor came from, preferring an explicit campaign tag.
     *
     * @return array{0: string, 1: string|null}
     */
    private function source(Request $request): array
    {
        $referrerHost = Str::lower((string) parse_url((string) $request->headers->get('referer'), PHP_URL_HOST));
        $referrerHost = $referrerHost !== '' ? Str::after($referrerHost, 'www.') : null;
        $campaign = Str::lower(trim((string) $request->query('utm_source')));

        foreach (self::SOURCES as $name => $domains) {
            if ($campaign !== '' && $this->campaignMatches($campaign, $name, $domains)) {
                return [$name, $referrerHost];
            }
        }

        foreach (self::SOURCES as $name => $domains) {
            if ($referrerHost !== null && $this->hostMatches($referrerHost, $domains)) {
                return [$name, $referrerHost];
            }
        }

        if ($campaign !== '') {
            return [Str::limit(Str::headline($campaign), 40, ''), $referrerHost];
        }

        if ($referrerHost === null || $referrerHost === Str::after($request->getHost(), 'www.')) {
            return ['Direct', null];
        }

        return ['Other websites', $referrerHost];
    }

    /**
     * Whether the host is one of the domains or a subdomain of one. A domain ending
     * in a dot, such as "google.", matches every country version.
     *
     * @param  list<string>  $domains
     */
    private function hostMatches(string $host, array $domains): bool
    {
        foreach ($domains as $domain) {
            $matches = str_ends_with($domain, '.')
                ? str_starts_with($host, $domain) || str_contains($host, '.'.$domain)
                : $host === $domain || str_ends_with($host, '.'.$domain);

            if ($matches) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether a utm_source tag names the source, such as "instagram" or "fb".
     *
     * @param  list<string>  $domains
     */
    private function campaignMatches(string $campaign, string $name, array $domains): bool
    {
        $aliases = array_filter(
            array_map(fn (string $domain): string => Str::before($domain, '.'), $domains),
            fn (string $alias): bool => strlen($alias) > 1,
        );

        return $campaign === Str::lower($name) || in_array($campaign, $aliases, true);
    }

    private function device(string $userAgent): string
    {
        return match (true) {
            preg_match('/ipad|tablet|kindle|silk|playbook|android(?!.*mobile)/i', $userAgent) === 1 => 'tablet',
            preg_match('/mobi|iphone|ipod|android|opera mini|iemobile/i', $userAgent) === 1 => 'mobile',
            default => 'desktop',
        };
    }
}
