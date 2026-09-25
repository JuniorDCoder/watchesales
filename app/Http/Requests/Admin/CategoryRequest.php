<?php

namespace App\Http\Requests\Admin;

use App\Models\Category;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
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
        /** @var Category|null $category */
        $category = $this->route('category');

        return [
            'name' => ['required', 'string', 'max:80', Rule::unique('categories', 'name')->ignore($category)],
            'slug' => ['nullable', 'string', 'max:100', 'alpha_dash', Rule::unique('categories', 'slug')->ignore($category)],
            'description' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,avif', 'max:8192'],
            'remove_image' => ['boolean'],
        ];
    }
}
