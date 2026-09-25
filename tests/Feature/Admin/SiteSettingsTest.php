<?php

use App\Models\Setting;
use App\Models\User;
use App\Support\SiteSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake('public');

    $this->actingAs(User::factory()->admin()->create());
});

/**
 * @return array<string, mixed>
 */
function settingsPayload(array $overrides = []): array
{
    return [
        'site_name' => 'Maison Horloge',
        'tagline' => 'Watches worth keeping',
        'inquiry_channel' => 'email',
        'contact_email' => 'hello@example.com',
        'floating_button_enabled' => true,
        'currency' => 'EUR',
        ...$overrides,
    ];
}

test('saved settings are shared with every storefront page', function () {
    $this->put(route('admin.settings.update'), settingsPayload([
        'inquiry_channel' => 'whatsapp',
        'whatsapp_number' => '+1 (929) 796-3621',
    ]))->assertRedirect(route('admin.settings.edit'));

    $this->get(route('home'))->assertInertia(fn (Assert $page) => $page
        ->where('site.name', 'Maison Horloge')
        ->where('site.currency', 'EUR')
        ->where('site.inquiry.channel', 'whatsapp')
        ->where('site.contact.whatsapp', '+1 (929) 796-3621')
    );
});

test('the chosen inquiry channel must be configured', function (string $channel, string $field) {
    $response = $this->put(route('admin.settings.update'), settingsPayload([
        'inquiry_channel' => $channel,
        'contact_email' => null,
    ]));

    $response->assertSessionHasErrors($field);
})->with([
    'live chat needs a website token' => ['chatwoot', 'chatwoot_website_token'],
    'whatsapp needs a number' => ['whatsapp', 'whatsapp_number'],
    'email needs an address' => ['email', 'contact_email'],
]);

test('chatwoot credentials are only shared once a token is saved', function () {
    $this->put(route('admin.settings.update'), settingsPayload([
        'inquiry_channel' => 'chatwoot',
        'chatwoot_base_url' => 'https://chat.example.com/',
        'chatwoot_website_token' => 'abc123XYZ',
    ]))->assertSessionHasNoErrors();

    $this->get(route('home'))->assertInertia(fn (Assert $page) => $page
        ->where('site.inquiry.chatwoot.baseUrl', 'https://chat.example.com')
        ->where('site.inquiry.chatwoot.websiteToken', 'abc123XYZ')
    );
});

test('chatwoot is shared for the chat bubble whichever inquiry channel is chosen', function () {
    $this->put(route('admin.settings.update'), settingsPayload([
        'inquiry_channel' => 'email',
        'chatwoot_base_url' => 'https://chat.example.com',
        'chatwoot_website_token' => 'abc123XYZ',
    ]))->assertSessionHasNoErrors();

    $this->get(route('home'))->assertInertia(fn (Assert $page) => $page
        ->where('site.inquiry.channel', 'email')
        ->where('site.inquiry.chatwoot.websiteToken', 'abc123XYZ')
    );
});

test('a chatwoot website token needs a chatwoot url', function () {
    $this->put(route('admin.settings.update'), settingsPayload([
        'chatwoot_base_url' => null,
        'chatwoot_website_token' => 'abc123XYZ',
    ]))->assertSessionHasErrors('chatwoot_base_url');
});

test('a new logo replaces the previous file', function () {
    $this->put(route('admin.settings.update'), settingsPayload(['logo' => UploadedFile::fake()->image('first.png')]));
    $firstPath = app(SiteSettings::class)->get('logo_path');

    $this->put(route('admin.settings.update'), settingsPayload(['logo' => UploadedFile::fake()->image('second.png')]));
    $secondPath = app(SiteSettings::class)->get('logo_path');

    expect($secondPath)->not->toBe($firstPath);
    Storage::disk('public')->assertMissing($firstPath);
    Storage::disk('public')->assertExists($secondPath);
});

test('a logo can be removed', function () {
    $this->put(route('admin.settings.update'), settingsPayload(['logo' => UploadedFile::fake()->image('logo.png')]));
    $path = app(SiteSettings::class)->get('logo_path');

    $this->put(route('admin.settings.update'), settingsPayload(['remove' => ['logo']]))->assertSessionHasNoErrors();

    expect(app(SiteSettings::class)->get('logo_path'))->toBeNull();
    Storage::disk('public')->assertMissing($path);
});

test('only supported currencies can be chosen', function () {
    $response = $this->put(route('admin.settings.update'), settingsPayload(['currency' => 'XYZ']));

    $response->assertSessionHasErrors(['currency' => 'The selected currency is invalid.']);
});

test('the store falls back to US dollars when the saved currency is not supported', function () {
    Setting::query()->create(['key' => 'currency', 'value' => 'XYZ']);

    $this->get(route('home'))->assertInertia(fn (Assert $page) => $page->where('site.currency', 'USD'));
});
