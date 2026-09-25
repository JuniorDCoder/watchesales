<?php

namespace App\Http\Resources;

use App\Models\Watch;
use Illuminate\Http\Request;

/**
 * The full watch record including its description and specifications.
 *
 * @mixin Watch
 */
class WatchDetailResource extends WatchResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'description' => $this->description,
            'brand_id' => $this->brand_id,
            'category_id' => $this->category_id,
            'case_material' => $this->case_material,
            'case_diameter' => $this->case_diameter !== null ? (float) $this->case_diameter : null,
            'water_resistance' => $this->water_resistance,
            'dial_color' => $this->dial_color,
            'strap_material' => $this->strap_material,
            'has_box' => $this->has_box,
            'has_papers' => $this->has_papers,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
        ];
    }
}
