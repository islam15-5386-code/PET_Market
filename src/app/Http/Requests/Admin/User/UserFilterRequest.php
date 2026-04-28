<?php

namespace App\Http\Requests\Admin\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UserFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search'   => ['sometimes', 'nullable', 'string', 'max:100'],
            'role'     => ['sometimes', 'nullable', 'in:user,admin'],
            'status'   => ['sometimes', 'nullable', 'in:active,blocked'],
            'per_page' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:50'],
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
