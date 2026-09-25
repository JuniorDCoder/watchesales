<?php

namespace App\Http\Controllers;

use App\Enums\WatchCondition;
use App\Enums\WatchGender;
use App\Enums\WatchMovement;
use App\Http\Resources\WatchDetailResource;
use App\Http\Resources\WatchResource;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Watch;
use App\Support\Analytics\VisitTracker;
use App\Support\Seo;
use App\Support\SiteSettings;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class WatchController extends Controller
{
    /**
     * Show the catalogue, optionally scoped to a category or brand landing page.
     */
    public function index(Request $request, VisitTracker $tracker, ?Category $category = null, ?Brand $brand = null): Response
    {
        $filters = $this->filters($request, $category, $brand);

        $watches = Watch::query()
            ->published()
            ->filter($filters)
            ->sortBy($filters['sort'])
            ->with(['brand', 'primaryImage'])
            ->paginate(12)
            ->withQueryString();

        if ($filters['search'] !== null && $watches->onFirstPage() && $tracker->shouldTrack($request)) {
            defer(fn () => $tracker->recordSearch($request, $filters['search'], $watches->total()));
        }

        $title = match (true) {
            $category !== null => $category->name,
            $brand !== null => "{$brand->name} watches",
            default => 'All watches',
        };

        $description = $category->description ?? $brand->description
            ?? 'Browse our full collection of authenticated new, pre-owned and vintage watches.';

        return Inertia::render('store/watches/Index', [
            'watches' => WatchResource::collection($watches),
            'filters' => $filters,
            'heading' => [
                'title' => $title,
                'description' => $description,
                'eyebrow' => $category ? 'Collection' : ($brand ? $brand->country : 'The collection'),
            ],
            'scope' => [
                'category' => $category?->slug,
                'brand' => $brand?->slug,
            ],
            'options' => [
                'categories' => Category::query()
                    ->ordered()
                    ->withCount('publishedWatches')
                    ->get(['id', 'name', 'slug'])
                    ->map(fn (Category $category): array => [
                        'id' => $category->id,
                        'name' => $category->name,
                        'slug' => $category->slug,
                        'watches_count' => $category->published_watches_count,
                    ]),
                'brands' => Brand::query()
                    ->has('publishedWatches')
                    ->withCount('publishedWatches')
                    ->orderBy('name')
                    ->get(['id', 'name', 'slug'])
                    ->map(fn (Brand $brand): array => [
                        'id' => $brand->id,
                        'name' => $brand->name,
                        'slug' => $brand->slug,
                        'watches_count' => $brand->published_watches_count,
                    ]),
                'conditions' => WatchCondition::options(),
                'movements' => WatchMovement::options(),
                'genders' => WatchGender::options(),
                'sorts' => [
                    ['value' => 'newest', 'label' => 'Newest arrivals'],
                    ['value' => 'price_asc', 'label' => 'Price, low to high'],
                    ['value' => 'price_desc', 'label' => 'Price, high to low'],
                    ['value' => 'popular', 'label' => 'Most viewed'],
                ],
            ],
            'seo' => Seo::page(
                title: $title,
                description: $description,
                indexable: ! $this->hasRefinements($request),
                jsonLd: [Seo::breadcrumbs(array_values(array_filter([
                    ['name' => 'Home', 'url' => route('home')],
                    ['name' => 'Watches', 'url' => route('watches.index')],
                    $category ? ['name' => $category->name, 'url' => route('collections.show', $category)] : null,
                    $brand ? ['name' => $brand->name, 'url' => route('brands.show', $brand)] : null,
                ])))],
            ),
        ]);
    }

    /**
     * Show a single watch.
     */
    public function show(Request $request, Watch $watch, SiteSettings $settings): Response
    {
        abort_unless($watch->is_published || $request->user()?->isAdmin(), 404);

        $watch->load(['brand', 'category', 'images', 'primaryImage']);

        $related = Watch::query()
            ->published()
            ->whereKeyNot($watch->id)
            ->where(fn (Builder $query) => $query
                ->where('category_id', $watch->category_id)
                ->orWhere('brand_id', $watch->brand_id))
            ->with(['brand', 'primaryImage'])
            ->orderByDesc('is_featured')
            ->latest()
            ->take(4)
            ->get();

        $fullName = "{$watch->brand->name} {$watch->name}";
        $description = $watch->meta_description ?: ($watch->summary ?: Str::limit((string) $watch->description, 200));

        return Inertia::render('store/watches/Show', [
            'watch' => (new WatchDetailResource($watch))->resolve($request),
            'related' => WatchResource::collection($related)->resolve($request),
            'seo' => Seo::page(
                title: $watch->meta_title ?: $fullName,
                description: $description,
                image: $watch->primaryImage?->url,
                type: 'product',
                indexable: $watch->is_published,
                jsonLd: [
                    $this->productSchema($watch, $fullName, $description, $settings->currency()),
                    Seo::breadcrumbs(array_values(array_filter([
                        ['name' => 'Home', 'url' => route('home')],
                        ['name' => 'Watches', 'url' => route('watches.index')],
                        $watch->category ? ['name' => $watch->category->name, 'url' => route('collections.show', $watch->category)] : null,
                        ['name' => $fullName, 'url' => route('watches.show', $watch)],
                    ]))),
                ],
            ),
        ]);
    }

    /**
     * Read the storefront filters from the query string, ignoring unknown values.
     *
     * @return array{search: string|null, category: string|null, brand: string|null, condition: string|null, movement: string|null, gender: string|null, min_price: int|null, max_price: int|null, available: bool, sort: string}
     */
    private function filters(Request $request, ?Category $category, ?Brand $brand): array
    {
        $search = Str::limit(trim((string) $request->string('search')), 80, '');

        return [
            'search' => $search !== '' ? $search : null,
            'category' => $category->slug ?? ($request->filled('category') ? (string) $request->string('category') : null),
            'brand' => $brand->slug ?? ($request->filled('brand') ? (string) $request->string('brand') : null),
            'condition' => WatchCondition::tryFrom((string) $request->string('condition'))?->value,
            'movement' => WatchMovement::tryFrom((string) $request->string('movement'))?->value,
            'gender' => WatchGender::tryFrom((string) $request->string('gender'))?->value,
            'min_price' => $request->integer('min_price') > 0 ? $request->integer('min_price') : null,
            'max_price' => $request->integer('max_price') > 0 ? $request->integer('max_price') : null,
            'available' => $request->boolean('available'),
            'sort' => array_key_exists((string) $request->string('sort'), Watch::SORTS) ? (string) $request->string('sort') : 'newest',
        ];
    }

    /**
     * Filtered and sorted listings are thin duplicates of the main pages, so keep them out of the index.
     */
    private function hasRefinements(Request $request): bool
    {
        return $request->hasAny(['search', 'category', 'brand', 'condition', 'movement', 'gender', 'min_price', 'max_price', 'available', 'sort']);
    }

    /**
     * @return array<string, mixed>
     */
    private function productSchema(Watch $watch, string $fullName, string $description, string $currency): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $fullName,
            'description' => $description,
            'sku' => $watch->reference,
            'mpn' => $watch->reference,
            'image' => $watch->images->pluck('url')->all(),
            'brand' => ['@type' => 'Brand', 'name' => $watch->brand->name],
            'category' => $watch->category?->name,
            'itemCondition' => $watch->condition->schemaCondition(),
            'offers' => $watch->price !== null ? [
                '@type' => 'Offer',
                'url' => route('watches.show', $watch),
                'price' => number_format((float) $watch->price, 2, '.', ''),
                'priceCurrency' => $currency,
                'availability' => $watch->status->schemaAvailability(),
                'itemCondition' => $watch->condition->schemaCondition(),
            ] : null,
        ];
    }
}
