<?php

namespace App\Http\Controllers\Api\Product;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\ProductFilterRequest;
use App\Http\Resources\ProductDetailResource;
use App\Http\Resources\ProductResource;
use App\Services\ProductService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    public function __construct(private readonly ProductService $productService) {}

    // ── GET /api/products ─────────────────────────────────────────────────────

    public function index(ProductFilterRequest $request): JsonResponse
    {
        $products = $this->productService->list($request->validated());

        return response()->json([
            'success' => true,
            'data'    => [
                'products' => ProductResource::collection($products),
                'meta'     => [
                    'current_page' => $products->currentPage(),
                    'last_page'    => $products->lastPage(),
                    'total_pages'  => $products->lastPage(),
                    'per_page'     => $products->perPage(),
                    'total'        => $products->total(),
                    'from'         => $products->firstItem(),
                    'to'           => $products->lastItem(),
                ],
                // Echo back active filters so frontend can restore state
                'filters' => [
                    'search'      => $request->search,
                    'category'    => $request->category,
                    'category_id' => $request->category_id ? (int) $request->category_id : null,
                    'min_price'   => $request->min_price ? (float) $request->min_price : null,
                    'max_price'   => $request->max_price ? (float) $request->max_price : null,
                    'location'    => $request->location,
                    'pet_type'    => $request->pet_type,
                    'age_group'   => $request->age_group,
                    'sort'        => $request->get('sort', 'newest'),
                ],
            ],
        ]);
    }

    // ── GET /api/products/{slug} ──────────────────────────────────────────────

    public function show(string $slug): JsonResponse
    {
        try {
            $product = $this->productService->findBySlug($slug);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => ['product' => new ProductDetailResource($product)],
        ]);
    }

    // ── GET /api/ai/suggestions ───────────────────────────────────────────────

    public function aiSuggestions(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'suggestions' => $this->productService->aiSuggestions(10),
            ],
        ]);
    }
}
