<?php

namespace App\Models;

use App\Concerns\HasUniqueSlug;
use App\Enums\WatchCondition;
use App\Enums\WatchGender;
use App\Enums\WatchMovement;
use App\Enums\WatchStatus;
use Carbon\CarbonImmutable;
use Database\Factories\WatchFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $brand_id
 * @property int|null $category_id
 * @property string $name
 * @property string $slug
 * @property string|null $reference
 * @property string|null $summary
 * @property string|null $description
 * @property string|null $price
 * @property WatchStatus $status
 * @property CarbonImmutable|null $sold_at
 * @property WatchCondition $condition
 * @property WatchMovement|null $movement
 * @property WatchGender $gender
 * @property int|null $year
 * @property string|null $case_material
 * @property string|null $case_diameter
 * @property int|null $water_resistance
 * @property string|null $dial_color
 * @property string|null $strap_material
 * @property bool $has_box
 * @property bool $has_papers
 * @property bool $is_featured
 * @property bool $is_published
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property int $views_count
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'brand_id',
    'category_id',
    'name',
    'slug',
    'reference',
    'summary',
    'description',
    'price',
    'status',
    'condition',
    'movement',
    'gender',
    'year',
    'case_material',
    'case_diameter',
    'water_resistance',
    'dial_color',
    'strap_material',
    'has_box',
    'has_papers',
    'is_featured',
    'is_published',
    'meta_title',
    'meta_description',
])]
class Watch extends Model
{
    /** @use HasFactory<WatchFactory> */
    use HasFactory, HasUniqueSlug;

    /**
     * Sort options offered on the storefront, keyed by their query string value.
     *
     * @var array<string, array{0: string, 1: 'asc'|'desc'}>
     */
    public const SORTS = [
        'newest' => ['created_at', 'desc'],
        'price_asc' => ['price', 'asc'],
        'price_desc' => ['price', 'desc'],
        'popular' => ['views_count', 'desc'],
    ];

    protected static function booted(): void
    {
        static::saving(function (Watch $watch): void {
            if (! $watch->isDirty('status')) {
                return;
            }

            $watch->sold_at = $watch->status === WatchStatus::Sold ? ($watch->sold_at ?? now()) : null;
        });

        static::deleting(function (Watch $watch): void {
            $watch->images->each->delete();
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'case_diameter' => 'decimal:1',
            'status' => WatchStatus::class,
            'sold_at' => 'datetime',
            'condition' => WatchCondition::class,
            'movement' => WatchMovement::class,
            'gender' => WatchGender::class,
            'has_box' => 'boolean',
            'has_papers' => 'boolean',
            'is_featured' => 'boolean',
            'is_published' => 'boolean',
        ];
    }

    protected function slugSource(): string
    {
        $brand = $this->brand_id ? Brand::query()->whereKey($this->brand_id)->value('name') : null;

        return trim("{$brand} {$this->name}");
    }

    /**
     * @return BelongsTo<Brand, $this>
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return HasMany<WatchImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(WatchImage::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @return HasOne<WatchImage, $this>
     */
    public function primaryImage(): HasOne
    {
        return $this->hasOne(WatchImage::class)->ofMany(['sort_order' => 'min', 'id' => 'min']);
    }

    /**
     * @return HasMany<PageView, $this>
     */
    public function pageViews(): HasMany
    {
        return $this->hasMany(PageView::class);
    }

    /**
     * @return HasMany<Inquiry, $this>
     */
    public function inquiries(): HasMany
    {
        return $this->hasMany(Inquiry::class);
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('is_published', true);
    }

    /**
     * Apply the storefront filters from the query string.
     *
     * @param  Builder<self>  $query
     * @param  array{search?: string|null, category?: string|null, brand?: string|null, condition?: string|null, movement?: string|null, gender?: string|null, min_price?: numeric|null, max_price?: numeric|null, available?: bool|null}  $filters
     */
    #[Scope]
    protected function filter(Builder $query, array $filters): void
    {
        $query
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('reference', 'like', "%{$search}%")
                        ->orWhereHas('brand', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($filters['category'] ?? null, fn (Builder $query, string $slug) => $query->whereHas(
                'category', fn (Builder $query) => $query->where('slug', $slug),
            ))
            ->when($filters['brand'] ?? null, fn (Builder $query, string $slug) => $query->whereHas(
                'brand', fn (Builder $query) => $query->where('slug', $slug),
            ))
            ->when($filters['condition'] ?? null, fn (Builder $query, string $condition) => $query->where('condition', $condition))
            ->when($filters['movement'] ?? null, fn (Builder $query, string $movement) => $query->where('movement', $movement))
            ->when($filters['gender'] ?? null, fn (Builder $query, string $gender) => $query->whereIn('gender', [$gender, WatchGender::Unisex->value]))
            ->when($filters['min_price'] ?? null, fn (Builder $query, $price) => $query->where('price', '>=', $price))
            ->when($filters['max_price'] ?? null, fn (Builder $query, $price) => $query->where('price', '<=', $price))
            ->when($filters['available'] ?? false, fn (Builder $query) => $query->where('status', WatchStatus::Available));
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function sortBy(Builder $query, ?string $sort): void
    {
        [$column, $direction] = self::SORTS[$sort] ?? self::SORTS['newest'];

        if ($column === 'price') {
            $query->orderByRaw('price is null');
        }

        $query->orderBy($column, $direction)->orderByDesc('id');
    }
}
