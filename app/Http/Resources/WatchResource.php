<?php

namespace App\Http\Resources;

use App\Models\Watch;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Watch
 */
class WatchResource extends JsonResource
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
            'name' => $this->name,
            'slug' => $this->slug,
            'reference' => $this->reference,
            'summary' => $this->summary,
            'price' => $this->price !== null ? (float) $this->price : null,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'condition' => $this->condition->value,
            'condition_label' => $this->condition->label(),
            'movement' => $this->movement?->value,
            'movement_label' => $this->movement?->label(),
            'gender' => $this->gender->value,
            'year' => $this->year,
            'is_featured' => $this->is_featured,
            'is_published' => $this->is_published,
            'brand' => $this->whenLoaded('brand', fn (): array => [
                'id' => $this->brand->id,
                'name' => $this->brand->name,
                'slug' => $this->brand->slug,
            ]),
            'category' => $this->whenLoaded('category', fn (): ?array => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ] : null),
            'image' => $this->whenLoaded('primaryImage', fn (): ?array => $this->primaryImage
                ? (new WatchImageResource($this->primaryImage))->resolve($request)
                : null),
            'images' => $this->whenLoaded('images', fn (): array => WatchImageResource::collection($this->images)->resolve($request)),
            'views_count' => $this->whenHas('views_count'),
            'inquiries_count' => $this->whenCounted('inquiries'),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
