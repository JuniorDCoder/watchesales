<?php

namespace App\Models;

use Database\Factories\PageViewFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A storefront page view by a human visitor. Visitors are identified by a
 * hash that changes every day, so no one can be followed across days.
 *
 * @property int $id
 * @property int|null $watch_id
 * @property string $path
 * @property string $visitor_hash
 * @property string $source
 * @property string|null $referrer_host
 * @property string $device
 * @property Carbon|null $created_at
 */
#[Fillable(['watch_id', 'path', 'visitor_hash', 'source', 'referrer_host', 'device'])]
class PageView extends Model
{
    /** @use HasFactory<PageViewFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    /**
     * @return BelongsTo<Watch, $this>
     */
    public function watch(): BelongsTo
    {
        return $this->belongsTo(Watch::class);
    }
}
