<?php

namespace App\Http\Requests\Admin;

use App\Enums\WatchCondition;
use App\Enums\WatchGender;
use App\Enums\WatchMovement;
use App\Enums\WatchStatus;
use App\Models\Watch;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WatchRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Watch|null $watch */
        $watch = $this->route('watch');

        return [
            'brand_id' => ['required', 'integer', Rule::exists('brands', 'id')],
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')],
            'name' => ['required', 'string', 'max:160'],
            'slug' => ['nullable', 'string', 'max:180', 'alpha_dash', Rule::unique('watches', 'slug')->ignore($watch)],
            'reference' => ['nullable', 'string', 'max:60'],
            'summary' => ['nullable', 'string', 'max:300'],
            'description' => ['nullable', 'string', 'max:10000'],
            'price' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'status' => ['required', Rule::enum(WatchStatus::class)],
            'condition' => ['required', Rule::enum(WatchCondition::class)],
            'movement' => ['nullable', Rule::enum(WatchMovement::class)],
            'gender' => ['required', Rule::enum(WatchGender::class)],
            'year' => ['nullable', 'integer', 'min:1800', 'max:'.(now()->year + 1)],
            'case_material' => ['nullable', 'string', 'max:80'],
            'case_diameter' => ['nullable', 'numeric', 'min:10', 'max:80'],
            'water_resistance' => ['nullable', 'integer', 'min:0', 'max:12000'],
            'dial_color' => ['nullable', 'string', 'max:60'],
            'strap_material' => ['nullable', 'string', 'max:80'],
            'has_box' => ['boolean'],
            'has_papers' => ['boolean'],
            'is_featured' => ['boolean'],
            'is_published' => ['boolean'],
            'meta_title' => ['nullable', 'string', 'max:70'],
            'meta_description' => ['nullable', 'string', 'max:320'],
            'images' => ['nullable', 'array', 'max:12'],
            'images.*' => WatchImageRequest::imageRules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'brand_id' => 'brand',
            'category_id' => 'category',
            'images.*' => 'image',
        ];
    }
}
