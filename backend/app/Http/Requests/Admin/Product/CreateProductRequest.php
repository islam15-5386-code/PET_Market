<?php

namespace App\Http\Requests\Admin\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // role:admin middleware handles access
    }

    public function rules(): array
    {
        return [
            'category_id'    => ['required', 'integer', 'exists:categories,id'],
            'name'           => ['required', 'string', 'min:3', 'max:255'],
            'description'    => ['nullable', 'string', 'max:5000'],
            'price'          => ['required', 'numeric', 'gt:0'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'location'       => ['nullable', 'string', 'max:150'],
            'brand'          => ['sometimes', 'nullable', 'string', 'max:120'],
            'sku'            => ['sometimes', 'nullable', 'string', 'max:80', 'unique:products,sku'],
            'rating'         => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:5'],
            'review_count'   => ['sometimes', 'nullable', 'integer', 'min:0'],
            'image_url'      => ['sometimes', 'nullable', 'url', 'max:1024'],
            'is_available'   => ['sometimes', 'boolean'],
            'is_active'      => ['sometimes', 'boolean'],
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
