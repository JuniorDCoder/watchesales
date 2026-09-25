<?php

use App\Models\User;

dataset('admin pages', [
    'watches' => ['admin.watches.index'],
    'new watch' => ['admin.watches.create'],
    'categories' => ['admin.categories.index'],
    'brands' => ['admin.brands.index'],
    'site settings' => ['admin.settings.edit'],
]);

test('guests are redirected to the login page', function (string $routeName) {
    $response = $this->get(route($routeName));

    $response->assertRedirect(route('login'));
})->with('admin pages');

test('users who are not admins are forbidden', function (string $routeName) {
    $this->actingAs(User::factory()->create());

    $response = $this->get(route($routeName));

    $response->assertForbidden();
})->with('admin pages');

test('admins can open every admin page', function (string $routeName) {
    $this->actingAs(User::factory()->admin()->create());

    $response = $this->get(route($routeName));

    $response->assertOk();
})->with('admin pages');

test('users who are not admins cannot change the catalogue', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->post(route('admin.brands.store'), ['name' => 'Rolex']);

    $response->assertForbidden();
    $this->assertDatabaseMissing('brands', ['name' => 'Rolex']);
});
