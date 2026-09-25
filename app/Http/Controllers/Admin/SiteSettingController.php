<?php

namespace App\Http\Controllers\Admin;

use App\Concerns\StoresPublicImages;
use App\Enums\InquiryChannel;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SiteSettingsRequest;
use App\Support\SiteSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class SiteSettingController extends Controller
{
    use StoresPublicImages;

    /**
     * Show the site settings form.
     */
    public function edit(SiteSettings $settings): Response
    {
        $values = [...$settings->all(), 'currency' => $settings->currency()];

        return Inertia::render('admin/Settings', [
            'settings' => collect($values)->except(SiteSettings::IMAGE_KEYS)->all(),
            'images' => collect(SiteSettingsRequest::UPLOADS)
                ->map(fn (string $key): ?string => $settings->imageUrl($key))
                ->all(),
            'channels' => InquiryChannel::options(),
            'currencies' => SiteSettings::CURRENCIES,
        ]);
    }

    /**
     * Save the site settings.
     */
    public function update(SiteSettingsRequest $request, SiteSettings $settings): RedirectResponse
    {
        $values = $request->safe()->except([...array_keys(SiteSettingsRequest::UPLOADS), 'remove']);
        $values['floating_button_enabled'] = $request->boolean('floating_button_enabled');

        $removals = $request->input('remove', []);

        foreach (SiteSettingsRequest::UPLOADS as $input => $key) {
            if (! $request->hasFile($input) && ! in_array($input, $removals, true)) {
                continue;
            }

            if ($previous = $settings->get($key)) {
                Storage::disk('public')->delete($previous);
            }

            $values[$key] = $request->hasFile($input)
                ? $this->storePublicImage($request->file($input), 'branding')
                : null;
        }

        $settings->update($values);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Site settings saved.']);

        return to_route('admin.settings.edit');
    }
}
