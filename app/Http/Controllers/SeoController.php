<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Watch;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    /**
     * Render the XML sitemap of every public page.
     */
    public function sitemap(): Response
    {
        $urls = collect([
            ['loc' => route('home'), 'priority' => '1.0'],
            ['loc' => route('watches.index'), 'priority' => '0.9'],
            ['loc' => route('about'), 'priority' => '0.5'],
            ['loc' => route('contact'), 'priority' => '0.5'],
        ])
            ->merge(Category::query()->ordered()->get()->map(fn (Category $category): array => [
                'loc' => route('collections.show', $category),
                'lastmod' => $category->updated_at?->toAtomString(),
                'priority' => '0.8',
            ]))
            ->merge(Brand::query()
                ->has('publishedWatches')
                ->get()
                ->map(fn (Brand $brand): array => [
                    'loc' => route('brands.show', $brand),
                    'lastmod' => $brand->updated_at?->toAtomString(),
                    'priority' => '0.7',
                ]))
            ->merge(Watch::query()->published()->latest('updated_at')->get(['id', 'slug', 'updated_at'])->map(fn (Watch $watch): array => [
                'loc' => route('watches.show', $watch),
                'lastmod' => $watch->updated_at?->toAtomString(),
                'priority' => '0.8',
            ]));

        return response()
            ->view('sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml');
    }

    /**
     * Point crawlers at the sitemap and away from the admin area.
     */
    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Disallow: /dashboard',
            'Disallow: /admin',
            'Disallow: /settings',
            'Disallow: /login',
            '',
            'Sitemap: '.route('sitemap'),
        ];

        return response(implode("\n", $lines)."\n")->header('Content-Type', 'text/plain');
    }
}
