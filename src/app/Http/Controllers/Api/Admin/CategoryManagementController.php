<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Services\Admin\CategoryManagementService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryManagementController extends Controller
{
    public function __construct(
        private readonly CategoryManagementService $categoryService
    ) {}

    // ── POST /api/admin/categories ────────────────────────────────────────────

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name'        => ['required', 'string', 'min:2', 'max:100', 'unique:categories,name'],
            'description' => ['nullable', 'string', 'max:500'],
            'icon'        => ['nullable', 'string', 'max:10'],
        ]);

        try {
            $category = $this->categoryService->create($request->all());
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Category created successfully.',
            'data'    => ['category' => new CategoryResource($category)],
        ], 201);
    }

    // ── PUT /api/admin/categories/{id} ────────────────────────────────────────

    public function update(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'name'        => ['sometimes', 'string', 'min:2', 'max:100'],
            'description' => ['sometimes', 'nullable', 'string', 'max:500'],
            'icon'        => ['sometimes', 'nullable', 'string', 'max:10'],
        ]);

        try {
            $category = Category::findOrFail($id);
            $category = $this->categoryService->update($category, $request->all());
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully.',
            'data'    => ['category' => new CategoryResource($category)],
        ]);
    }

    // ── DELETE /api/admin/categories/{id} ─────────────────────────────────────

    public function destroy(int $id): JsonResponse
    {
        try {
            $category = Category::findOrFail($id);
            $this->categoryService->delete($category);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found.',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully.',
        ]);
    }
}
