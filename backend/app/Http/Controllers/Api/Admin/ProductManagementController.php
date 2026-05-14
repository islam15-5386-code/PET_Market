<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Product\CreateProductRequest;
use App\Http\Requests\Admin\Product\UpdateProductRequest;
use App\Http\Resources\Admin\AdminProductResource;
use App\Models\AiProductDescription;
use App\Models\Product;
use App\Services\Admin\ProductManagementService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

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

    // ── POST /api/admin/products/{id}/generate-description ───────────────────

    public function generateDescription(int $id): JsonResponse
    {
        try {
            $product = Product::with('category')->findOrFail($id);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
            ], 404);
        }

        $compact = [
            'product_name' => $product->name,
            'category' => $product->category?->name,
            'pet_type' => $this->guessPetType($product),
            'age_group' => $this->guessAgeGroup($product),
            'price' => (float) $product->price,
            'brand' => $product->brand,
            'key_features' => $this->extractFeatures($product),
            'target_customer' => 'Bangladesh pet owners',
            'language' => 'English',
            'tone' => 'professional',
        ];

        $compact = array_filter($compact, fn ($value) => $value !== null && $value !== '');
        $promptHash = hash('sha256', json_encode($compact));

        $cached = AiProductDescription::query()
            ->where('product_id', $product->id)
            ->where('prompt_hash', $promptHash)
            ->latest('id')
            ->first();

        if ($cached) {
            return response()->json([
                'success' => true,
                'message' => 'Cached AI description loaded.',
                'data' => [
                    'source' => $cached->source,
                    'title' => $cached->title,
                    'description' => $cached->description,
                    'seo_keywords' => $cached->seo_keywords,
                    'benefits' => $cached->benefits,
                    'cached' => true,
                ],
            ]);
        }

        $aiBaseUrl = rtrim(config('services.ai_service.url', env('AI_SERVICE_URL', 'http://127.0.0.1:8001')), '/');

        try {
            $aiResponse = Http::timeout(20)->post("{$aiBaseUrl}/ai/product-description/generate", $compact);
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

        $payload = $aiResponse->json();
        $record = AiProductDescription::create([
            'product_id' => $product->id,
            'title' => (string) ($payload['professional_product_title'] ?? $product->name),
            'description' => (string) ($payload['short_description'] ?? ''),
            'seo_keywords' => (array) ($payload['seo_keywords'] ?? []),
            'benefits' => (array) ($payload['benefits'] ?? []),
            'source' => (string) ($payload['provider_name'] ?? 'template_fallback'),
            'prompt_hash' => $promptHash,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'AI description generated successfully.',
            'data' => [
                'source' => $record->source,
                'title' => $record->title,
                'description' => $record->description,
                'seo_keywords' => $record->seo_keywords,
                'benefits' => $record->benefits,
                'cached' => false,
            ],
        ]);
    }

    private function extractFeatures(Product $product): array
    {
        $base = [];
        if ($product->brand) {
            $base[] = "{$product->brand} quality";
        }
        if ($product->category?->name) {
            $base[] = strtolower($product->category->name) . " focused";
        }
        if ($product->is_available) {
            $base[] = "ready stock";
        }
        if ($product->description) {
            $parts = preg_split('/[,.]/', strip_tags($product->description)) ?: [];
            foreach ($parts as $part) {
                $trim = trim($part);
                if (Str::length($trim) >= 8) {
                    $base[] = Str::limit($trim, 55, '');
                }
                if (count($base) >= 5) {
                    break;
                }
            }
        }
        return array_values(array_unique(array_slice($base, 0, 5)));
    }

    private function guessPetType(Product $product): ?string
    {
        $haystack = strtolower($product->name . ' ' . ($product->category?->name ?? '') . ' ' . ($product->description ?? ''));
        if (str_contains($haystack, 'cat') || str_contains($haystack, 'kitten')) return 'Cat';
        if (str_contains($haystack, 'dog') || str_contains($haystack, 'puppy')) return 'Dog';
        if (str_contains($haystack, 'bird') || str_contains($haystack, 'parrot')) return 'Bird';
        if (str_contains($haystack, 'fish') || str_contains($haystack, 'aquarium')) return 'Fish';
        if (str_contains($haystack, 'rabbit') || str_contains($haystack, 'hamster')) return 'Small Animal';
        return null;
    }

    private function guessAgeGroup(Product $product): ?string
    {
        $haystack = strtolower($product->name . ' ' . ($product->description ?? ''));
        if (str_contains($haystack, 'kitten')) return 'Kitten';
        if (str_contains($haystack, 'puppy')) return 'Puppy';
        if (str_contains($haystack, 'adult')) return 'Adult';
        if (str_contains($haystack, 'senior')) return 'Senior';
        return null;
    }
}
