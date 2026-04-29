<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Product\CreateProductRequest;
use App\Http\Requests\Admin\Product\UpdateProductRequest;
use App\Http\Resources\Admin\AdminProductResource;
use App\Models\Product;
use App\Services\Admin\ProductManagementService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductManagementController extends Controller
{
    public function __construct(
        private readonly ProductManagementService $productService
    ) {}

    // ── GET /api/admin/products ───────────────────────────────────────────────

    public function index(Request $request): JsonResponse
    {
        $products = $this->productService->list($request->all());

        return response()->json([
            'success' => true,
            'data'    => [
                'products' => AdminProductResource::collection($products),
                'meta'     => [
                    'current_page' => $products->currentPage(),
                    'last_page'    => $products->lastPage(),
                    'per_page'     => $products->perPage(),
                    'total'        => $products->total(),
                ],
            ],
        ]);
    }

    // ── POST /api/admin/products ──────────────────────────────────────────────

    public function store(CreateProductRequest $request): JsonResponse
    {
        $product = $this->productService->create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Product created successfully.',
            'data'    => ['product' => new AdminProductResource($product->load('category'))],
        ], 201);
    }

    // ── GET /api/admin/products/{id} ──────────────────────────────────────────

    public function show(int $id): JsonResponse
    {
        try {
            $product = Product::with('category')->withTrashed()->findOrFail($id);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => ['product' => new AdminProductResource($product)],
        ]);
    }

    // ── PUT /api/admin/products/{id} ──────────────────────────────────────────

    public function update(UpdateProductRequest $request, int $id): JsonResponse
    {
        try {
            $product = Product::withTrashed()->findOrFail($id);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
            ], 404);
        }

        $product = $this->productService->update($product, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully.',
            'data'    => ['product' => new AdminProductResource($product)],
        ]);
    }

    // ── DELETE /api/admin/products/{id} ───────────────────────────────────────

    public function destroy(int $id): JsonResponse
    {
        try {
            $product = Product::findOrFail($id);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
            ], 404);
        }

        $this->productService->delete($product);

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully.',
        ]);
    }

    // ── POST /api/admin/products/{id}/images ──────────────────────────────────

    public function uploadImages(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'images'   => ['required', 'array', 'min:1', 'max:5'],
            'images.*' => ['image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ]);

        try {
            $product = Product::findOrFail($id);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
            ], 404);
        }

        try {
            $product = $this->productService->uploadImages($product, $request->file('images'));
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Images uploaded successfully.',
            'data'    => ['product' => new AdminProductResource($product)],
        ]);
    }

    // ── DELETE /api/admin/products/{id}/images/{index} ────────────────────────

    public function deleteImage(int $id, int $index): JsonResponse
    {
        try {
            $product = Product::findOrFail($id);
            $product = $this->productService->deleteImage($product, $index);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Image deleted successfully.',
            'data'    => ['product' => new AdminProductResource($product)],
        ]);
    }
}
