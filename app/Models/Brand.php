<?php

namespace App\Models;

use App\Concerns\HasUniqueSlug;
use Database\Factories\BrandFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $country
 * @property string|null $description
 * @property-read int|null $published_watches_count
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'slug', 'country', 'description'])]
class Brand extends Model
{
    /** @use HasFactory<BrandFactory> */
    use HasFactory, HasUniqueSlug;

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
}
