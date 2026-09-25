<?php

namespace App\Http\Requests\Admin;

use App\Enums\InquiryChannel;
use App\Support\SiteSettings;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SiteSettingsRequest extends FormRequest
{
    /**
     * Uploadable images mapped to the setting that stores their path.
     *
     * @var array<string, string>
     */
    public const UPLOADS = [
        'logo' => 'logo_path',
        'logo_dark' => 'logo_dark_path',
        'favicon' => 'favicon_path',
        'og_image' => 'og_image_path',
        'hero_image' => 'hero_image_path',
    ];

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $image = ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,avif', 'max:8192'];

        return [
            'site_name' => ['required', 'string', 'max:60'],
            'tagline' => ['nullable', 'string', 'max:120'],
            'announcement' => ['nullable', 'string', 'max:160'],
            'logo' => $image,
            'logo_dark' => $image,
            'favicon' => ['nullable', 'image', 'mimes:png,webp,ico', 'max:1024'],
            'og_image' => $image,
            'hero_image' => $image,
            'remove' => ['array'],
            'remove.*' => [Rule::in(array_keys(self::UPLOADS))],

            'hero_eyebrow' => ['nullable', 'string', 'max:60'],
            'hero_title' => ['nullable', 'string', 'max:120'],
            'hero_subtitle' => ['nullable', 'string', 'max:400'],
            'about_title' => ['nullable', 'string', 'max:120'],
            'about_body' => ['nullable', 'string', 'max:5000'],

            'inquiry_channel' => ['required', Rule::enum(InquiryChannel::class)],
            'floating_button_enabled' => ['boolean'],
            'contact_email' => ['nullable', 'required_if:inquiry_channel,email', 'email', 'max:120'],
            'contact_phone' => ['nullable', 'string', 'max:40'],
            'whatsapp_number' => ['nullable', 'required_if:inquiry_channel,whatsapp', 'string', 'regex:/^\+?[0-9\s\-()]{7,20}$/'],
            'address' => ['nullable', 'string', 'max:255'],
            'business_hours' => ['nullable', 'string', 'max:120'],
            'chatwoot_base_url' => ['nullable', 'required_if:inquiry_channel,chatwoot', 'required_with:chatwoot_website_token', 'url:https,http', 'max:255'],
            'chatwoot_website_token' => ['nullable', 'required_if:inquiry_channel,chatwoot', 'string', 'alpha_num', 'max:120'],

            'currency' => ['required', Rule::in(SiteSettings::CURRENCIES)],

            'meta_title' => ['nullable', 'string', 'max:70'],
            'meta_description' => ['nullable', 'string', 'max:160'],

            'instagram_url' => ['nullable', 'url:https', 'max:255'],
            'facebook_url' => ['nullable', 'url:https', 'max:255'],
            'x_url' => ['nullable', 'url:https', 'max:255'],
            'tiktok_url' => ['nullable', 'url:https', 'max:255'],
            'youtube_url' => ['nullable', 'url:https', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'contact_email.required_if' => 'An email address is required when email is the inquiry channel.',
            'whatsapp_number.required_if' => 'A WhatsApp number is required when WhatsApp is the inquiry channel.',
            'whatsapp_number.regex' => 'Enter the WhatsApp number in international format, for example +1 (929) 796-3621.',
            'chatwoot_base_url.required_if' => 'The Chatwoot URL is required when live chat is the inquiry channel.',
            'chatwoot_base_url.required_with' => 'The Chatwoot URL is required when a website token is set.',
            'chatwoot_website_token.required_if' => 'The Chatwoot website token is required when live chat is the inquiry channel.',
        ];
    }
}
