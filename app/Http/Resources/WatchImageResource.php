<?php

namespace App\Http\Resources;

use App\Models\WatchImage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin WatchImage
 */
class WatchImageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'url' => $this->url,
            'alt' => $this->alt,
            'sort_order' => $this->sort_order,
        ];
    }
}
