<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class AIController extends Controller
{
    public function productSearch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => ['required', 'string', 'min:2', 'max:500'],
        ]);

        $aiBaseUrl = rtrim(config('services.ai_service.url', env('AI_SERVICE_URL')), '/');

        try {
            $aiResponse = Http::timeout(15)->post("{$aiBaseUrl}/ai/product-search", [
                'query' => $validated['query'],
                'user_id' => optional(auth('api')->user())->id,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'AI service is unavailable right now.',
                'error' => $e->getMessage(),
            ], 503);
        }

        if (!$aiResponse->successful()) {
            return response()->json([
                'success' => false,
                'message' => 'AI service returned an error.',
                'ai_status' => $aiResponse->status(),
                'ai_response' => $aiResponse->json(),
            ], 502);
        }

        $filters = $aiResponse->json();

        $query = Product::query()
            ->with('category')
            ->where('stock_quantity', '>', 0)
            ->where('is_available', true);

        if (!empty($filters['category'])) {
            $categoryName = $filters['category'];
            $query->whereHas('category', function ($q) use ($categoryName) {
                $op = DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';
                $q->where('name', $op, $categoryName);
            });
        }

        if (!empty($filters['max_price'])) {
            $query->where('price', '<=', (float) $filters['max_price']);
        }

        if (!empty($filters['min_price'])) {
            $query->where('price', '>=', (float) $filters['min_price']);
        }

        if (!empty($filters['location'])) {
            $op = DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->where('location', $op, '%' . $filters['location'] . '%');
        }

        if (!empty($filters['brand'])) {
            $op = DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->where('brand', $op, '%' . $filters['brand'] . '%');
        }

        if (!empty($filters['keywords']) && is_array($filters['keywords'])) {
            $op = DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->where(function ($q) use ($filters, $op) {
                foreach ($filters['keywords'] as $kw) {
                    $q->orWhere('name', $op, "%{$kw}%")
                      ->orWhere('description', $op, "%{$kw}%")
                      ->orWhere('brand', $op, "%{$kw}%")
                      ->orWhere('location', $op, "%{$kw}%");
                }
            });
        }

        $query->orderByDesc('stock_quantity');
        if (!empty($filters['max_price'])) {
            $query->orderBy('price', 'asc');
        } else {
            $query->latest('created_at');
        }

        $products = $query->limit(30)->get();

        return response()->json([
            'success' => true,
            'data' => [
                'query' => $validated['query'],
                'ai_filters' => $filters,
                'products' => ProductResource::collection($products),
            ],
        ]);
    }
}
