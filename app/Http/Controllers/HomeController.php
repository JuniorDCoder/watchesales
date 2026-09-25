<?php

namespace App\Http\Controllers;

use App\Enums\WatchStatus;
use App\Http\Resources\WatchResource;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Watch;
use App\Models\WatchImage;
use App\Support\Seo;
use App\Support\SiteSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    /**
     * Show the storefront home page.
     */
    public function __invoke(Request $request, SiteSettings $settings): Response
    {
        $featured = Watch::query()
            ->published()
            ->where('is_featured', true)
            ->with(['brand', 'primaryImage'])
            ->latest()
            ->take(6)
            ->get();

        $latest = Watch::query()
            ->published()
            ->with(['brand', 'primaryImage'])
            ->latest()
            ->take(8)
            ->get();

        $siteName = (string) $settings->get('site_name');

        return Inertia::render('store/Home', [
            'hero' => [
                'eyebrow' => $settings->get('hero_eyebrow'),
                'title' => $settings->get('hero_title'),
                'subtitle' => $settings->get('hero_subtitle'),
                'imageUrl' => $settings->imageUrl('hero_image_path'),
            ],
            'featured' => WatchResource::collection($featured)->resolve($request),
            'latest' => WatchResource::collection($latest)->resolve($request),
            'categories' => $this->categories(),
            'brands' => Brand::query()
                ->has('publishedWatches')
                ->orderBy('name')
                ->get(['id', 'name', 'slug']),
            'stats' => [
                'available' => Watch::query()->published()->where('status', WatchStatus::Available)->count(),
                'brands' => Brand::query()->count(),
            ],
            'seo' => Seo::page(
                title: $settings->get('meta_title') ?: $settings->get('tagline'),
                jsonLd: [[
                    '@context' => 'https://schema.org',
                    '@type' => 'Store',
                    'name' => $siteName,
                    'description' => $settings->get('meta_description'),
                    'url' => url('/'),
                    'logo' => $settings->imageUrl('logo_path'),
                    'email' => $settings->get('contact_email'),
                    'telephone' => $settings->get('contact_phone'),
                    'address' => $settings->get('address'),
                ], [
                    '@context' => 'https://schema.org',
                    '@type' => 'WebSite',
                    'name' => $siteName,
                    'url' => url('/'),
                    'potentialAction' => [
                        '@type' => 'SearchAction',
                        'target' => route('watches.index').'?search={search_term_string}',
                        'query-input' => 'required name=search_term_string',
                    ],
                ]],
            ),
        ]);
    }

    /**
     * Categories with a cover image, falling back to a photo of one of their watches.
     *
     * @return list<array<string, mixed>>
     */
    private function categories(): array
    {
        return array_values(Category::query()
            ->ordered()
            ->withCount('publishedWatches')
            ->addSelect(['cover_path' => WatchImage::query()
                ->select('watch_images.path')
                ->join('watches', 'watches.id', '=', 'watch_images.watch_id')
                ->whereColumn('watches.category_id', 'categories.id')
                ->where('watches.is_published', true)
                ->orderByDesc('watches.is_featured')
                ->orderBy('watch_images.sort_order')
                ->limit(1),
            ])
            ->get()
            ->filter(fn (Category $category): bool => $category->published_watches_count > 0)
            ->map(fn (Category $category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'count' => $category->published_watches_count,
                'imageUrl' => $category->image_url
                    ?? ($category->getAttribute('cover_path') ? Storage::disk('public')->url($category->getAttribute('cover_path')) : null),
            ])
            ->all());
    }
}
