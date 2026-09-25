<?php

use App\Models\User;
use App\Models\Watch;
use App\Support\SiteSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    Http::preventStrayRequests();
    Http::fake([
        'images.unsplash.com/*' => Http::response(UploadedFile::fake()->image('watch.jpg')->getContent()),
    ]);
});

test('seeding creates the admin and a photographed catalogue', function () {
    config(['app.admin.email' => 'owner@example.com']);

    $this->seed();

    $admin = User::query()->sole();
    expect($admin->email)->toBe('owner@example.com')
        ->and($admin->isAdmin())->toBeTrue()
        ->and(Watch::query()->count())->toBe(12)
        ->and(Watch::query()->doesntHave('images')->count())->toBe(0)
        ->and(app(SiteSettings::class)->forFrontend())
        ->toMatchArray(['currency' => 'USD'])
        ->inquiry->channel->toBe('whatsapp')
        ->contact->whatsapp->toBe('+1 (929) 796-3621');
});

test('seeding twice does not duplicate records', function () {
    $this->seed();
    $this->seed();

    expect(User::query()->count())->toBe(1)
        ->and(Watch::query()->count())->toBe(12);
});
