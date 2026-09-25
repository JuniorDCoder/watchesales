<?php

namespace App\Models;

use App\Concerns\HasUniqueSlug;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string|null $image_path
 * @property int $sort_order
 * @property-read string|null $image_url
 * @property-read int|null $published_watches_count
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'slug', 'description', 'image_path', 'sort_order'])]
#[Appends(['image_url'])]
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory, HasUniqueSlug;

    protected static function booted(): void
    {
        static::deleted(function (Category $category): void {
            if ($category->image_path) {
                Storage::disk('public')->delete($category->image_path);
            }
        });
    }

    /**
     * @return HasMany<Watch, $this>
     */
    public function watches(): HasMany
    {
        return $this->hasMany(Watch::class);
    }

    /**
     * Watches visible on the storefront.
     *
     * @return HasMany<Watch, $this>
     */
    public function publishedWatches(): HasMany
    {
        return $this->watches()->where('is_published', true);
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * @return Attribute<string|null, never>
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->image_path
            ? Storage::disk('public')->url($this->image_path)
            : null);
    }
}
