<?php

namespace App\Http\Controllers;

use App\Enums\WatchStatus;
use App\Models\Brand;
use App\Models\Watch;
use App\Support\Seo;
use App\Support\SiteSettings;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    /**
     * Show the about page.
     */
    public function about(SiteSettings $settings): Response
    {
        return Inertia::render('store/About', [
            'about' => [
                'title' => $settings->get('about_title'),
                'body' => $settings->get('about_body'),
                'imageUrl' => $settings->imageUrl('hero_image_path'),
            ],
            'stats' => [
                'available' => Watch::query()->published()->where('status', WatchStatus::Available)->count(),
                'sold' => Watch::query()->where('status', WatchStatus::Sold)->count(),
                'brands' => Brand::query()->count(),
            ],
            'seo' => Seo::page(title: 'About us', description: $settings->get('about_body')),
        ]);
    }

    /**
     * Show the contact page.
     */
    public function contact(SiteSettings $settings): Response
    {
        return Inertia::render('store/Contact', [
            'seo' => Seo::page(
                title: 'Contact',
                description: "Speak with a specialist at {$settings->get('site_name')} about any watch in our collection.",
            ),
        ]);
    }
}
