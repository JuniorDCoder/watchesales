<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * Admin editable site configuration, stored as key and value rows and cached as one array.
 */
#[Scoped]
class SiteSettings
{
    public const CACHE_KEY = 'site-settings';

    /**
     * Settings that hold a path on the public disk.
     *
     * @var list<string>
     */
    public const IMAGE_KEYS = ['logo_path', 'logo_dark_path', 'favicon_path', 'og_image_path', 'hero_image_path'];

    /**
     * The currency used when none is configured or the stored one is no longer supported.
     */
    public const DEFAULT_CURRENCY = 'USD';

    /**
     * ISO 4217 codes the admin may choose as the store currency.
     *
     * @var list<string>
     */
    public const CURRENCIES = [
        'USD', 'EUR', 'GBP', 'CHF', 'CAD', 'AUD', 'NZD', 'JPY', 'CNY', 'HKD', 'SGD',
        'AED', 'SAR', 'QAR', 'INR', 'NGN', 'GHS', 'KES', 'ZAR', 'EGP', 'BRL', 'MXN',
    ];

    /**
     * Every supported setting with its default value.
     *
     * @var array<string, string|bool|null>
     */
    public const DEFAULTS = [
        'site_name' => 'Tempora',
        'tagline' => 'Fine watches, personally sourced',
        'announcement' => null,
        'logo_path' => null,
        'logo_dark_path' => null,
        'favicon_path' => null,

        'hero_eyebrow' => 'Independent watch dealer',
        'hero_title' => 'Timepieces chosen with intent.',
        'hero_subtitle' => 'Every watch in our collection is inspected, authenticated and photographed in house. Browse at your own pace and speak with a specialist whenever you are ready.',
        'hero_image_path' => null,
        'about_title' => 'Sourced by people who wear them.',
        'about_body' => "We started as collectors trading with friends, and we still run the business that way. Every piece is checked by hand, its history is documented, and nothing is listed until we would happily wear it ourselves.\n\nWhen you reach out, you speak to the person who inspected the watch. No call centres, no pressure, just honest advice and a straight answer on price, condition and provenance.",

        'contact_email' => null,
        'contact_phone' => null,
        'whatsapp_number' => null,
        'address' => null,
        'business_hours' => null,

        'inquiry_channel' => 'email',
        'floating_button_enabled' => true,
        'chatwoot_base_url' => 'https://app.chatwoot.com',
        'chatwoot_website_token' => null,

        'currency' => self::DEFAULT_CURRENCY,

        'meta_title' => null,
        'meta_description' => 'Authenticated luxury and collectible watches. Browse new, pre-owned and vintage pieces and speak directly with a specialist.',
        'og_image_path' => null,

        'instagram_url' => null,
        'facebook_url' => null,
        'x_url' => null,
        'tiktok_url' => null,
        'youtube_url' => null,
    ];

    /**
     * The resolved settings for the current request.
     *
     * @var array<string, mixed>|null
     */
    private ?array $resolved = null;

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->resolved ??= Cache::rememberForever(self::CACHE_KEY, fn (): array => array_merge(
            self::DEFAULTS,
            Setting::query()->whereIn('key', array_keys(self::DEFAULTS))->pluck('value', 'key')->all(),
        ));
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    /**
     * Persist the given settings and refresh the cache.
     *
     * @param  array<string, mixed>  $values
     */
    public function update(array $values): void
    {
        foreach (Arr::only($values, array_keys(self::DEFAULTS)) as $key => $value) {
            Setting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }

        Cache::forget(self::CACHE_KEY);

        $this->resolved = null;
    }

    /**
     * The store currency, falling back to US dollars.
     */
    public function currency(): string
    {
        $currency = $this->get('currency');

        return in_array($currency, self::CURRENCIES, true) ? $currency : self::DEFAULT_CURRENCY;
    }

    /**
     * Resolve a stored image setting to its public URL.
     */
    public function imageUrl(string $key): ?string
    {
        $path = $this->get($key);

        return $path ? Storage::disk('public')->url($path) : null;
    }

    /**
     * The settings every storefront page needs, shaped for the frontend.
     *
     * @return array<string, mixed>
     */
    public function forFrontend(): array
    {
        $settings = $this->all();

        return [
            'name' => $settings['site_name'],
            'tagline' => $settings['tagline'],
            'announcement' => $settings['announcement'],
            'logoUrl' => $this->imageUrl('logo_path'),
            'logoDarkUrl' => $this->imageUrl('logo_dark_path'),
            'faviconUrl' => $this->imageUrl('favicon_path'),
            'currency' => $this->currency(),
            'contact' => [
                'email' => $settings['contact_email'],
                'phone' => $settings['contact_phone'],
                'whatsapp' => $settings['whatsapp_number'],
                'address' => $settings['address'],
                'hours' => $settings['business_hours'],
            ],
            'inquiry' => [
                'channel' => $settings['inquiry_channel'],
                'floatingButton' => (bool) $settings['floating_button_enabled'],
                'chatwoot' => $settings['chatwoot_website_token'] ? [
                    'baseUrl' => rtrim((string) $settings['chatwoot_base_url'], '/'),
                    'websiteToken' => $settings['chatwoot_website_token'],
                ] : null,
            ],
            'social' => array_filter([
                'instagram' => $settings['instagram_url'],
                'facebook' => $settings['facebook_url'],
                'x' => $settings['x_url'],
                'tiktok' => $settings['tiktok_url'],
                'youtube' => $settings['youtube_url'],
            ]),
        ];
    }
}
