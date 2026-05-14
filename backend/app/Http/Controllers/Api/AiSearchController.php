<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Services\AiSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiSearchController extends Controller
{
    public function __construct(private readonly AiSearchService $searchService)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => ['required', 'string', 'min:2', 'max:500'],
        ]);

        $userId = optional(auth('api')->user())->id;
        $result = $this->searchService->search(trim($validated['query']), $userId);

        return response()->json([
            'success' => true,
            'data' => [
                'query' => trim($validated['query']),
                'ai_filters' => $result['ai_filters'],
                'strategy_used' => $result['strategy_used'],
                'token_usage' => $result['token_usage'],
                'exact_results' => ProductResource::collection($result['exact_results']),
                'fallback_results' => ProductResource::collection($result['fallback_results']),
                'products' => ProductResource::collection($result['products']),
                'similar_products' => ProductResource::collection($result['fallback_results']),
                'result_mode' => $result['result_mode'],
                'message' => $result['message'],
            ],
        ]);
    }
}
