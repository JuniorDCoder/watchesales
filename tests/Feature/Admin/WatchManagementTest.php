<?php

use App\Enums\WatchStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\User;
use App\Models\Watch;
use App\Models\WatchImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');

    $this->actingAs(User::factory()->admin()->create());
});

/**
 * @return array<string, mixed>
 */
function watchPayload(array $overrides = []): array
{
    return [
        'brand_id' => Brand::factory()->create(['name' => 'Rolex'])->id,
        'category_id' => Category::factory()->create()->id,
        'name' => 'Submariner Date',
        'reference' => '126610LN',
        'price' => '14950',
        'status' => 'available',
        'condition' => 'pre_owned',
        'movement' => 'automatic',
        'gender' => 'men',
        'has_box' => true,
        'has_papers' => false,
        'is_featured' => true,
        'is_published' => true,
        ...$overrides,
    ];
}

test('admins can add a watch with photos', function () {
    $response = $this->post(route('admin.watches.store'), watchPayload([
        'images' => [
            UploadedFile::fake()->image('front.jpg', 800, 1000),
            UploadedFile::fake()->image('side.jpg', 800, 1000),
        ],
    ]));

    $watch = Watch::query()->sole();

    $response->assertRedirect(route('admin.watches.edit', $watch));
    expect($watch->slug)->toBe('rolex-submariner-date')
        ->and($watch->price)->toBe('14950.00')
        ->and($watch->has_box)->toBeTrue()
        ->and($watch->images)->toHaveCount(2)
        ->and($watch->images->pluck('sort_order')->all())->toBe([0, 1]);

    $watch->images->each(fn (WatchImage $image) => Storage::disk('public')->assertExists($image->path));
});

test('a watch without a price is saved as price on request', function () {
    $this->post(route('admin.watches.store'), watchPayload(['price' => null]))
        ->assertSessionHasNoErrors();

    expect(Watch::query()->sole()->price)->toBeNull();
});

test('watches that share a name receive unique slugs', function () {
    $brand = Brand::factory()->create(['name' => 'Omega']);

    $this->post(route('admin.watches.store'), watchPayload(['brand_id' => $brand->id, 'name' => 'Speedmaster']));
    $this->post(route('admin.watches.store'), watchPayload(['brand_id' => $brand->id, 'name' => 'Speedmaster']));

    expect(Watch::query()->orderBy('id')->pluck('slug')->all())
        ->toBe(['omega-speedmaster', 'omega-speedmaster-2']);
});

test('a watch needs a name, brand and valid status', function () {
    $response = $this->post(route('admin.watches.store'), [
        'status' => 'lost',
        'condition' => 'new',
        'gender' => 'unisex',
    ]);

    $response->assertSessionHasErrors([
        'name' => 'The name field is required.',
        'brand_id' => 'The brand field is required.',
        'status' => 'The selected status is invalid.',
    ]);
    expect(Watch::query()->count())->toBe(0);
});

test('only image files can be uploaded as watch photos', function () {
    $response = $this->post(route('admin.watches.store'), watchPayload([
        'images' => [UploadedFile::fake()->create('invoice.pdf', 100, 'application/pdf')],
    ]));

    $response->assertSessionHasErrors('images.0');
    expect(Watch::query()->count())->toBe(0);
});

test('admins can update a watch', function () {
    $watch = Watch::factory()->create();

    $response = $this->put(route('admin.watches.update', $watch), watchPayload([
        'name' => 'Explorer 39',
        'status' => 'sold',
        'is_published' => false,
    ]));

    $response->assertRedirect(route('admin.watches.edit', $watch));
    $watch->refresh();
    expect($watch->name)->toBe('Explorer 39')
        ->and($watch->status)->toBe(WatchStatus::Sold)
        ->and($watch->is_published)->toBeFalse();
});

test('status and visibility can be changed from the watch list', function () {
    $watch = Watch::factory()->create(['is_featured' => false]);

    $this->patch(route('admin.watches.attributes.update', $watch), [
        'status' => 'reserved',
        'is_featured' => true,
    ])->assertRedirect();

    $watch->refresh();
    expect($watch->status)->toBe(WatchStatus::Reserved)
        ->and($watch->is_featured)->toBeTrue();
});

test('deleting a watch removes its photos from storage', function () {
    $watch = Watch::factory()->create();
    $path = UploadedFile::fake()->image('front.jpg')->store("watches/{$watch->id}", 'public');
    $watch->images()->create(['path' => $path, 'sort_order' => 0]);

    $response = $this->delete(route('admin.watches.destroy', $watch));

    $response->assertRedirect(route('admin.watches.index'));
    $this->assertModelMissing($watch);
    $this->assertDatabaseCount('watch_images', 0);
    Storage::disk('public')->assertMissing($path);
});

test('photos can be added to an existing watch', function () {
    $watch = Watch::factory()->create();
    WatchImage::factory()->for($watch)->create(['sort_order' => 0]);

    $this->post(route('admin.watches.images.store', $watch), [
        'images' => [UploadedFile::fake()->image('extra.jpg')],
    ])->assertSessionHasNoErrors();

    expect($watch->images()->pluck('sort_order')->all())->toBe([0, 1]);
});

test('photos can be reordered', function () {
    $watch = Watch::factory()->create();
    $images = collect(range(0, 2))->map(fn (int $position) => WatchImage::factory()->for($watch)->create(['sort_order' => $position]));

    $this->put(route('admin.watches.images.reorder', $watch), [
        'order' => $images->reverse()->pluck('id')->values()->all(),
    ])->assertSessionHasNoErrors();

    expect($watch->images()->pluck('id')->all())->toBe($images->reverse()->pluck('id')->values()->all())
        ->and($watch->primaryImage->id)->toBe($images->last()->id);
});

test('photos from another watch cannot be reordered into this one', function () {
    $watch = Watch::factory()->create();
    $ownImage = WatchImage::factory()->for($watch)->create();
    $otherImage = WatchImage::factory()->create();

    $response = $this->put(route('admin.watches.images.reorder', $watch), [
        'order' => [$ownImage->id, $otherImage->id],
    ]);

    $response->assertSessionHasErrors('order.1');
});

test('a photo can only be deleted through its own watch', function () {
    $watch = Watch::factory()->create();
    $otherImage = WatchImage::factory()->create();

    $response = $this->delete(route('admin.watches.images.destroy', [$watch, $otherImage]));

    $response->assertNotFound();
    $this->assertModelExists($otherImage);
});
