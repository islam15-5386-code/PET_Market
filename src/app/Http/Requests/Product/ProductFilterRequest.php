<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ProductFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search'      => ['sometimes', 'nullable', 'string', 'max:150'],
            'category'    => ['sometimes', 'nullable', 'string', 'exists:categories,slug'],
            'category_id' => ['sometimes', 'nullable', 'integer', 'exists:categories,id'],
            'min_price'   => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'max_price'   => ['sometimes', 'nullable', 'numeric', 'min:0', 'gte:min_price'],
            'location'    => ['sometimes', 'nullable', 'string', 'max:100'],
            'pet_type'    => ['sometimes', 'nullable', 'string', 'max:60'],
            'age_group'   => ['sometimes', 'nullable', 'string', 'max:120'],
            'sort'        => ['sometimes', 'nullable', 'in:price_asc,price_desc,price_low,price_high,newest,oldest,rating'],
            'per_page'    => ['sometimes', 'nullable', 'integer', 'min:1', 'max:50'],
            'limit'       => ['sometimes', 'nullable', 'integer', 'min:1', 'max:50'],
        ];
    }

    protected function failedValidation(Validator $validator): never
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Invalid filter parameters.',
            'errors'  => $validator->errors(),
        ], 422));
    }
}
