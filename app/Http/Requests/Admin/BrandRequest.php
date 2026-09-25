<?php

namespace App\Http\Requests\Admin;

use App\Models\Brand;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BrandRequest extends FormRequest
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
        /** @var Brand|null $brand */
        $brand = $this->route('brand');

        return [
            'name' => ['required', 'string', 'max:80', Rule::unique('brands', 'name')->ignore($brand)],
            'slug' => ['nullable', 'string', 'max:100', 'alpha_dash', Rule::unique('brands', 'slug')->ignore($brand)],
            'country' => ['nullable', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }
}
