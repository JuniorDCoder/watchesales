<?php

use App\Models\Inquiry;
use App\Models\PageView;
use App\Models\SearchQuery;
use App\Models\User;
use App\Models\Watch;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('users who are not admins cannot visit the dashboard', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->get(route('dashboard'));
    $response->assertForbidden();
});

test('admins can visit the dashboard', function () {
    $this->actingAs(User::factory()->admin()->create());

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('visits, inquiries and the inquiry rate are compared with the previous period', function () {
    PageView::factory()->count(2)->create(['visitor_hash' => 'same-person']);
    PageView::factory()->create(['visitor_hash' => 'someone-else']);
    PageView::factory()->create(['created_at' => now()->subDays(40)]);
    Inquiry::factory()->create();
    Inquiry::factory()->create(['created_at' => now()->subDays(45)]);

    $this->actingAs(User::factory()->admin()->create());

    $response = $this->get(route('dashboard'));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('Dashboard')
        ->where('period.days', 30)
        ->where('kpis.visits', ['current' => 2, 'previous' => 1])
        ->where('kpis.inquiries', ['current' => 1, 'previous' => 1])
        ->where('kpis.inquiryRate', ['current' => 50, 'previous' => 100])
        ->has('series', 30)
    );
});

test('the reporting period can be changed', function (mixed $requested, int $expected) {
    $this->actingAs(User::factory()->admin()->create());

    $response = $this->get(route('dashboard', ['period' => $requested]));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('period.days', $expected)
        ->has('series', $expected)
    );
})->with([
    'a week' => [7, 7],
    'a quarter' => [90, 90],
    'an unsupported period' => [365, 30],
]);

test('visits are broken down by source and device', function () {
    PageView::factory()->count(3)->sequence(fn ($sequence) => ['visitor_hash' => "visitor-{$sequence->index}"])->create(['source' => 'Instagram', 'device' => 'mobile']);
    PageView::factory()->create(['source' => 'Google', 'device' => 'desktop']);

    $this->actingAs(User::factory()->admin()->create());

    $response = $this->get(route('dashboard'));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('sources.0', ['name' => 'Instagram', 'visits' => 3, 'share' => 75])
        ->where('devices.0', ['name' => 'Mobile', 'visits' => 3, 'share' => 75])
        ->where('insights', fn ($insights) => collect($insights)->contains('text', 'Instagram is your biggest source of visitors, bringing 75% of visits.'))
    );
});

test('each watch shows its views, inquiries and inquiry rate', function () {
    $watch = Watch::factory()->create();
    PageView::factory()->count(20)->create(['watch_id' => $watch->id]);
    Inquiry::factory()->count(2)->for($watch)->create();

    $this->actingAs(User::factory()->admin()->create());

    $response = $this->get(route('dashboard'));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('watches.0.id', $watch->id)
        ->where('watches.0.views', 20)
        ->where('watches.0.inquiries', 2)
        ->where('watches.0.conversion', 10)
    );
});

test('watches that are viewed but never enquired about need attention', function () {
    $watch = Watch::factory()->create(['name' => 'Speedmaster']);
    PageView::factory()->count(12)->create(['watch_id' => $watch->id]);

    $this->actingAs(User::factory()->admin()->create());

    $response = $this->get(route('dashboard'));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('attention.0.type', 'no_inquiries')
        ->where('attention.0.watchId', $watch->id)
        ->where('insights', fn ($insights) => collect($insights)->contains(
            fn (array $insight) => $insight['tone'] === 'negative' && str_contains($insight['text'], 'Speedmaster had 12 views but no inquiries'),
        ))
    );
});

test('searches that found nothing are reported as unmet demand', function () {
    SearchQuery::factory()->count(3)->create(['term' => 'patek philippe', 'results_count' => 0]);
    SearchQuery::factory()->create(['term' => 'rolex', 'results_count' => 4]);

    $this->actingAs(User::factory()->admin()->create());

    $response = $this->get(route('dashboard'));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('searches.total', 4)
        ->where('searches.unmet', [['term' => 'patek philippe', 'count' => 3]])
    );
});

test('sales in the period and the average time to sell are reported', function () {
    Watch::factory()->create(['price' => 9000, 'status' => 'sold', 'created_at' => now()->subDays(20)]);
    Watch::factory()->create(['price' => 4000]);

    $this->actingAs(User::factory()->admin()->create());

    $response = $this->get(route('dashboard'));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('inventory.soldInPeriod', 1)
        ->where('inventory.soldValueInPeriod', 9000)
        ->where('inventory.availableValue', 4000)
        ->where('inventory.averageDaysToSell', 20)
    );
});
