<?php

namespace App\Http\Controllers\Api\Product;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function __construct(private readonly ProductService $productService) {}

    // ── GET /api/categories ───────────────────────────────────────────────────

    public function index(): JsonResponse
    {
        $categories = $this->productService->allCategories();

        return response()->json([
            'success' => true,
            'data'    => [
                'categories' => CategoryResource::collection($categories),
            ],
        ]);
    }
}
