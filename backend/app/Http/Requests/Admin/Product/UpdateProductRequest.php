<?php

namespace App\Http\Requests\Admin\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id'    => ['sometimes', 'integer', 'exists:categories,id'],
            'name'           => ['sometimes', 'string', 'min:3', 'max:255'],
            'description'    => ['sometimes', 'nullable', 'string', 'max:5000'],
            'price'          => ['sometimes', 'numeric', 'gt:0'],
            'stock_quantity' => ['sometimes', 'integer', 'min:0'],
            'location'       => ['sometimes', 'nullable', 'string', 'max:150'],
            'brand'          => ['sometimes', 'nullable', 'string', 'max:120'],
            'pet_type'       => ['sometimes', 'nullable', 'string', 'max:60'],
            'age_group'      => ['sometimes', 'nullable', 'string', 'max:60'],
            'tags'           => ['sometimes', 'nullable', 'array', 'max:20'],
            'tags.*'         => ['string', 'max:80'],
            'sku'            => ['sometimes', 'nullable', 'string', 'max:80', Rule::unique('products', 'sku')->ignore($this->route('id'))],
            'rating'         => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:5'],
            'review_count'   => ['sometimes', 'nullable', 'integer', 'min:0'],
            'image_url'      => ['sometimes', 'nullable', 'url', 'max:1024'],
            'is_available'   => ['sometimes', 'boolean'],
            'is_active'      => ['sometimes', 'boolean'],
            'ai_generated_title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'ai_generated_short_description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'ai_generated_long_description' => ['sometimes', 'nullable', 'string', 'max:10000'],
            'ai_seo_keywords' => ['sometimes', 'nullable', 'array', 'max:10'],
            'ai_seo_keywords.*' => ['string', 'max:120'],
            'ai_meta_title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'ai_meta_description' => ['sometimes', 'nullable', 'string', 'max:500'],
            'ai_generated_tags' => ['sometimes', 'nullable', 'array', 'max:20'],
            'ai_generated_tags.*' => ['string', 'max:80'],
        ];
    }

    protected function failedValidation(Validator $validator): never
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation failed.',
            'errors'  => $validator->errors(),
        ], 422));
    }
}
