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
            'sort'        => ['sometimes', 'nullable', 'in:price_asc,price_desc,newest,oldest'],
            'per_page'    => ['sometimes', 'nullable', 'integer', 'min:1', 'max:100'],
            'limit'       => ['sometimes', 'nullable', 'integer', 'min:1', 'max:100'],
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
