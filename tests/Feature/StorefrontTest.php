<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\User;
use App\Models\Watch;
use App\Support\SiteSettings;
use Inertia\Testing\AssertableInertia as Assert;

test('the home page shows featured watches that are published', function () {
    $featured = Watch::factory()->featured()->create();
    Watch::factory()->featured()->unpublished()->create();
    Watch::factory()->create();

    $response = $this->get(route('home'));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('store/Home')
        ->has('featured', 1)
        ->where('featured.0.id', $featured->id)
        ->has('latest', 2)
    );
});

test('the catalogue hides unpublished watches', function () {
    $published = Watch::factory()->create();
    Watch::factory()->unpublished()->create();

    $response = $this->get(route('watches.index'));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('store/watches/Index')
        ->has('watches.data', 1)
        ->where('watches.data.0.id', $published->id)
    );
});

test('the catalogue can be filtered', function (array $query, string $expectedName) {
    $rolex = Brand::factory()->create(['name' => 'Rolex']);
    Watch::factory()->for($rolex)->create(['name' => 'Explorer', 'condition' => 'pre_owned', 'price' => 8000]);
    Watch::factory()->create(['name' => 'Heritage Chronograph', 'condition' => 'new', 'price' => 1600, 'reference' => 'T66']);

    $response = $this->get(route('watches.index', $query));

    $response->assertInertia(fn (Assert $page) => $page
        ->has('watches.data', 1)
        ->where('watches.data.0.name', $expectedName)
    );
})->with([
    'by brand' => [['brand' => 'rolex'], 'Explorer'],
    'by search on brand name' => [['search' => 'Rolex'], 'Explorer'],
    'by search on reference' => [['search' => 'T66'], 'Heritage Chronograph'],
    'by condition' => [['condition' => 'new'], 'Heritage Chronograph'],
    'by maximum price' => [['max_price' => 2000], 'Heritage Chronograph'],
]);

test('available only hides sold and reserved watches', function () {
    Watch::factory()->sold()->create();
    Watch::factory()->create(['status' => 'reserved']);
    $available = Watch::factory()->create();

    $response = $this->get(route('watches.index', ['available' => 1]));

    $response->assertInertia(fn (Assert $page) => $page
        ->has('watches.data', 1)
        ->where('watches.data.0.id', $available->id)
    );
});

test('filtered listings are kept out of search engines', function () {
    $this->get(route('watches.index'))
        ->assertInertia(fn (Assert $page) => $page->where('seo.robots', 'index, follow'));

    $this->get(route('watches.index', ['sort' => 'price_asc']))
        ->assertInertia(fn (Assert $page) => $page->where('seo.robots', 'noindex, follow'));
});

test('a collection page only lists watches in that category', function () {
    $category = Category::factory()->create(['name' => 'Dive Watches']);
    $diver = Watch::factory()->for($category)->create();
    Watch::factory()->create();

    $response = $this->get(route('collections.show', $category));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('heading.title', 'Dive Watches')
        ->where('scope.category', 'dive-watches')
        ->has('watches.data', 1)
        ->where('watches.data.0.id', $diver->id)
    );
});

test('an unknown collection shows the not found page', function () {
    $response = $this->get('/collections/does-not-exist');

    $response->assertNotFound()
        ->assertInertia(fn (Assert $page) => $page->component('store/Error')->where('status', 404));
});

test('viewing a watch counts the view and describes it for search engines', function () {
    $watch = Watch::factory()->create(['price' => 14950, 'reference' => '126610LN']);

    $response = $this->get(route('watches.show', $watch));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('store/watches/Show')
        ->where('watch.id', $watch->id)
        ->where('seo.type', 'product')
        ->where('seo.jsonLd.0.@type', 'Product')
        ->where('seo.jsonLd.0.sku', '126610LN')
        ->where('seo.jsonLd.0.offers.price', '14950.00')
    );
    $response->assertSee('"@type":"Product"', false);
    expect($watch->refresh()->views_count)->toBe(1);
});

test('watches priced on request do not advertise an offer', function () {
    $watch = Watch::factory()->create(['price' => null]);

    $response = $this->get(route('watches.show', $watch));

    $response->assertInertia(fn (Assert $page) => $page->missing('seo.jsonLd.0.offers'));
});

test('unpublished watches are hidden from visitors', function () {
    $watch = Watch::factory()->unpublished()->create();

    $this->get(route('watches.show', $watch))->assertNotFound();
});

test('admins can preview unpublished watches without counting a view', function () {
    $watch = Watch::factory()->unpublished()->create();

    $response = $this->actingAs(User::factory()->admin()->create())->get(route('watches.show', $watch));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page->where('seo.robots', 'noindex, follow'));
    expect($watch->refresh()->views_count)->toBe(0);
});

test('the sitemap lists public pages but not unpublished watches', function () {
    $published = Watch::factory()->create();
    $hidden = Watch::factory()->unpublished()->create();

    $response = $this->get(route('sitemap'));

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/xml')
        ->assertSee(route('watches.show', $published), false)
        ->assertSee(route('collections.show', $published->category), false)
        ->assertDontSee(route('watches.show', $hidden), false);
});

test('robots.txt points crawlers to the sitemap and away from the admin area', function () {
    $response = $this->get('/robots.txt');

    $response->assertOk()
        ->assertSee('Sitemap: '.route('sitemap'))
        ->assertSee('Disallow: /admin');
});

test('search engines see prices in the store currency', function () {
    app(SiteSettings::class)->update(['currency' => 'EUR']);
    $watch = Watch::factory()->create(['price' => 5000]);

    $response = $this->get(route('watches.show', $watch));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('site.currency', 'EUR')
        ->where('seo.jsonLd.0.offers.priceCurrency', 'EUR')
    );
});
