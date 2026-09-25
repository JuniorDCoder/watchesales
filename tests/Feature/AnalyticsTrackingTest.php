<?php

use App\Enums\WatchStatus;
use App\Models\PageView;
use App\Models\SearchQuery;
use App\Models\User;
use App\Models\Watch;

const IPHONE = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148 Safari/604.1';
const DESKTOP = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_0) AppleWebKit/537.36 Chrome/126.0 Safari/537.36';

test('viewing a watch records where the visitor came from and on what device', function () {
    $watch = Watch::factory()->create();

    $this->withHeaders(['User-Agent' => IPHONE, 'Referer' => 'https://www.google.com/'])
        ->get(route('watches.show', $watch))
        ->assertOk();

    $view = PageView::query()->sole();
    expect($view->watch_id)->toBe($watch->id)
        ->and($view->path)->toBe("/watches/{$watch->slug}")
        ->and($view->source)->toBe('Google')
        ->and($view->referrer_host)->toBe('google.com')
        ->and($view->device)->toBe('mobile')
        ->and($watch->refresh()->views_count)->toBe(1);
});

test('the source is worked out from the referrer or campaign tag', function (array $headers, string $query, string $expected) {
    $this->withHeaders(['User-Agent' => DESKTOP, ...$headers])->get('/'.$query);

    expect(PageView::query()->sole()->source)->toBe($expected);
})->with([
    'no referrer' => [[], '', 'Direct'],
    'instagram link' => [['Referer' => 'https://l.instagram.com/'], '', 'Instagram'],
    'campaign tag' => [[], '?utm_source=facebook', 'Facebook'],
    'unknown website' => [['Referer' => 'https://watchforum.example/thread'], '', 'Other websites'],
    'lookalike domain' => [['Referer' => 'https://discount.co/deal'], '', 'Other websites'],
    'country search engine' => [['Referer' => 'https://www.google.co.uk/'], '', 'Google'],
]);

test('refreshing the same page is only counted once', function () {
    $watch = Watch::factory()->create();

    $this->withHeaders(['User-Agent' => DESKTOP])->get(route('watches.show', $watch));
    $this->withHeaders(['User-Agent' => DESKTOP])->get(route('watches.show', $watch));

    expect(PageView::query()->count())->toBe(1)
        ->and($watch->refresh()->views_count)->toBe(1);
});

test('crawlers, prefetches and staff are not counted', function (Closure $request) {
    $watch = Watch::factory()->create();

    $request($this, route('watches.show', $watch));

    expect(PageView::query()->count())->toBe(0)
        ->and($watch->refresh()->views_count)->toBe(0);
})->with([
    'search engine crawler' => [fn ($test, string $url) => $test->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1)'])->get($url)],
    'link preview' => [fn ($test, string $url) => $test->withHeaders(['User-Agent' => 'WhatsApp/2.23.20.0'])->get($url)],
    'hover prefetch' => [fn ($test, string $url) => $test->withHeaders(['User-Agent' => DESKTOP, 'Purpose' => 'prefetch'])->get($url)],
    'admin' => [fn ($test, string $url) => $test->actingAs(User::factory()->admin()->create())->withHeaders(['User-Agent' => DESKTOP])->get($url)],
]);

test('searches are recorded with how many watches matched', function () {
    Watch::factory()->create(['name' => 'Explorer']);

    $this->withHeaders(['User-Agent' => DESKTOP])->get(route('watches.index', ['search' => 'Explorer']));
    $this->withHeaders(['User-Agent' => DESKTOP])->get(route('watches.index', ['search' => 'Patek']));

    expect(SearchQuery::query()->orderBy('id')->get(['term', 'results_count'])->toArray())->toBe([
        ['term' => 'explorer', 'results_count' => 1],
        ['term' => 'patek', 'results_count' => 0],
    ]);
});

test('a search typed in stages is stored once', function () {
    foreach (['ro', 'rol', 'rolex'] as $term) {
        $this->withHeaders(['User-Agent' => DESKTOP])->get(route('watches.index', ['search' => $term]));
    }

    expect(SearchQuery::query()->pluck('term')->all())->toBe(['rolex']);
});

test('marking a watch as sold records when it sold', function () {
    $watch = Watch::factory()->create();

    $watch->update(['status' => WatchStatus::Sold]);
    expect($watch->refresh()->sold_at)->not->toBeNull();

    $watch->update(['status' => WatchStatus::Available]);
    expect($watch->refresh()->sold_at)->toBeNull();
});
