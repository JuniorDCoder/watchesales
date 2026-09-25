<?php

namespace App\Models;

use App\Enums\InquiryChannel;
use Database\Factories\InquiryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A record of a visitor reaching out through one of the contact channels.
 *
 * @property int $id
 * @property int|null $watch_id
 * @property InquiryChannel $channel
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['watch_id', 'channel'])]
class Inquiry extends Model
{
    /** @use HasFactory<InquiryFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'channel' => InquiryChannel::class,
        ];
    }

    /**
     * @return BelongsTo<Watch, $this>
     */
    public function watch(): BelongsTo
    {
        return $this->belongsTo(Watch::class);
    }
}
