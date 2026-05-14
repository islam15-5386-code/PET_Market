<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AiProductDescriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiProductDescriptionController extends Controller
{
    public function __construct(private readonly AiProductDescriptionService $service)
    {
    }

    public function generate(Request $request): JsonResponse
    {
        $user = auth('api')->user();
        if (!$user || !in_array($user->role, ['admin', 'seller'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Only admin/seller can generate AI description.',
            ], 403);
        }

        $validated = $request->validate([
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'product_name' => ['required', 'string', 'min:2', 'max:255'],
            'category' => ['required', 'string', 'max:120'],
            'pet_type' => ['required', 'string', 'max:50'],
            'age_group' => ['nullable', 'string', 'max:50'],
            'brand' => ['nullable', 'string', 'max:120'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'weight_or_size' => ['nullable', 'string', 'max:100'],
            'ingredients_or_materials' => ['nullable'],
            'key_features' => ['nullable'],
            'usage_instruction' => ['nullable', 'string', 'max:1000'],
            'safety_note' => ['nullable', 'string', 'max:1000'],
            'target_customer' => ['nullable', 'string', 'max:255'],
            'language' => ['required', 'in:English,Bangla,Bangla-English mixed'],
            'tone' => ['required', 'in:professional,friendly,SEO optimized'],
        ]);

        $generated = $this->service->generate($validated, $user->id);

        return response()->json([
            'success' => true,
            'data' => $generated,
        ]);
    }
}
