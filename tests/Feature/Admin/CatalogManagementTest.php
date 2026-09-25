<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\User;
use App\Models\Watch;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');

    $this->actingAs(User::factory()->admin()->create());
});

test('admins can create a category with a cover image', function () {
    $response = $this->post(route('admin.categories.store'), [
        'name' => 'Dive Watches',
        'description' => 'Built for the deep.',
        'sort_order' => 2,
        'image' => UploadedFile::fake()->image('cover.jpg'),
    ]);

    $response->assertRedirect();
    $category = Category::query()->sole();
    expect($category->slug)->toBe('dive-watches')
        ->and($category->sort_order)->toBe(2);
    Storage::disk('public')->assertExists($category->image_path);
});

test('category names must be unique', function () {
    Category::factory()->create(['name' => 'Chronographs']);

    $response = $this->post(route('admin.categories.store'), ['name' => 'Chronographs']);

    $response->assertSessionHasErrors(['name' => 'The name has already been taken.']);
});

test('removing a category cover image deletes the file', function () {
    $path = UploadedFile::fake()->image('cover.jpg')->store('categories', 'public');
    $category = Category::factory()->create(['image_path' => $path]);

    $this->put(route('admin.categories.update', $category), [
        'name' => $category->name,
        'remove_image' => true,
    ])->assertSessionHasNoErrors();

    expect($category->refresh()->image_path)->toBeNull();
    Storage::disk('public')->assertMissing($path);
});

test('deleting a category keeps its watches without a category', function () {
    $category = Category::factory()->create();
    $watch = Watch::factory()->for($category)->create();

    $this->delete(route('admin.categories.destroy', $category))->assertRedirect();

    $this->assertModelMissing($category);
    expect($watch->refresh()->category_id)->toBeNull();
});

test('admins can create and rename a brand', function () {
    $this->post(route('admin.brands.store'), ['name' => 'Grand Seiko', 'country' => 'Japan'])
        ->assertSessionHasNoErrors();

    $brand = Brand::query()->sole();
    expect($brand->slug)->toBe('grand-seiko');

    $this->put(route('admin.brands.update', $brand), ['name' => 'Grand Seiko', 'slug' => 'gs'])
        ->assertSessionHasNoErrors();

    expect($brand->refresh()->slug)->toBe('gs');
});

test('a brand that still has watches cannot be deleted', function () {
    $brand = Brand::factory()->create();
    Watch::factory()->for($brand)->create();

    $response = $this->delete(route('admin.brands.destroy', $brand));

    $response->assertRedirect()->assertInertiaFlash('toast.type', 'error');
    $this->assertModelExists($brand);
});

test('a brand without watches can be deleted', function () {
    $brand = Brand::factory()->create();

    $this->delete(route('admin.brands.destroy', $brand))->assertRedirect();

    $this->assertModelMissing($brand);
});
