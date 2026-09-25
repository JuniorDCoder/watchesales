<?php

namespace App\Models;

use Database\Factories\WatchImageFactory;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int $watch_id
 * @property string $path
 * @property string|null $alt
 * @property int $sort_order
 * @property-read string $url
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['path', 'alt', 'sort_order'])]
#[Appends(['url'])]
class WatchImage extends Model
{
    /** @use HasFactory<WatchImageFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::deleted(function (WatchImage $image): void {
            Storage::disk('public')->delete($image->path);
        });
    }

    /**
     * @return BelongsTo<Watch, $this>
     */
    public function watch(): BelongsTo
    {
        return $this->belongsTo(Watch::class);
    }

    /**
     * @return Attribute<string, never>
     */
    protected function url(): Attribute
    {
        return Attribute::get(fn (): string => Storage::disk('public')->url($this->path));
    }
}
