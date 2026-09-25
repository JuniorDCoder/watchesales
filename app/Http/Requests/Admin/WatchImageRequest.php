<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class WatchImageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    /**
     * Rules shared by every watch photo upload.
     *
     * @return list<string>
     */
    public static function imageRules(): array
    {
        return ['image', 'mimes:jpg,jpeg,png,webp,avif', 'max:8192'];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'images' => ['required', 'array', 'min:1', 'max:12'],
            'images.*' => self::imageRules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'images.*' => 'image',
        ];
    }
}
