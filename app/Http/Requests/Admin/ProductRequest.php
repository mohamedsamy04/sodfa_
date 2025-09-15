<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
{
    return [
        'name' => 'required|string|max:255',
        'category_id' => 'required|exists:categories,id',
        'description' => 'required|string',
        'product_advantage' => 'nullable|string',
        'price' => 'required|numeric',
        'is_featured' => 'boolean',

        'colors' => 'required|array|min:1',
        'colors.*.color_id' => 'required|exists:colors,id',
        'colors.*.images' => 'required|array|min:1',
        'colors.*.images.*' => [
            function ($attribute, $value, $fail) {
                if (empty($value)) {
                    $fail($attribute.' is required (URL).');
                } elseif (!filter_var($value, FILTER_VALIDATE_URL)) {
                    $fail($attribute.' must be a valid URL.');
                }
            },
        ],
    ];
}

}
