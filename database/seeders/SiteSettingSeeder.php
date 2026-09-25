<?php

namespace Database\Seeders;

use App\Support\SiteSettings;
use Database\Seeders\Concerns\DownloadsImages;
use Illuminate\Database\Seeder;

class SiteSettingSeeder extends Seeder
{
    use DownloadsImages;

    /**
     * Give the storefront sensible contact details and a hero photograph.
     */
    public function run(SiteSettings $settings): void
    {
        $settings->update([
            'contact_email' => 'concierge@example.com',
            'business_hours' => 'Monday to Saturday, 10:00 to 19:00',
            'address' => 'Viewings by appointment',
            'whatsapp_number' => '+1 (929) 796-3621',
            'inquiry_channel' => 'whatsapp',
            'currency' => SiteSettings::DEFAULT_CURRENCY,
            'announcement' => 'Complimentary insured shipping on every watch',
            'hero_image_path' => $this->downloadImage(
                $this->unsplashUrl('1533139502658-0198f920d8e8', ['w' => 2400, 'h' => 1600]),
                'branding/hero.jpg',
            ),
        ]);
    }
}
