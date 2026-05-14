<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\ProductImageService;
use Illuminate\Console\Command;

class VerifyProductImagesCommand extends Command
{
    protected $signature = 'products:verify-images {--limit=0}';
    protected $description = 'Verify product image_url consistency against pet type/category/sub-category mapping';

    public function handle(ProductImageService $imageService): int
    {
        $limit = max(0, (int) $this->option('limit'));

        $query = Product::query()->with('category');
        if ($limit > 0) {
            $query->limit($limit);
        }

        $total = $query->count();
        if ($total === 0) {
            $this->warn('No products found.');
            return self::SUCCESS;
        }

        $valid = 0;
        $fallback = 0;
        $mismatched = 0;
        $missing = 0;

        $query->orderBy('id')->chunkById(1000, function ($products) use (
            &$valid,
            &$fallback,
            &$mismatched,
            &$missing,
            $imageService
        ) {
            foreach ($products as $product) {
                $petType = $imageService->inferPetTypeFromProduct($product);
                $categoryType = $imageService->inferCategoryFromProduct($product);
                $subCategory = $imageService->inferSubCategoryFromProduct($product);

                $current = $product->image_url ?: (($product->images ?? [])[0] ?? null);

                if (!$current) {
                    $missing++;
                    continue;
                }

                if ($current === '/products/fallback/pet-product-placeholder.jpg') {
                    $fallback++;
                    continue;
                }

                if ($this->matchesMappingPath($current, $petType, $categoryType)) {
                    $valid++;
                } else {
                    $mismatched++;
                }
            }
        });

        $this->line("Total products checked: {$total}");
        $this->line("Products with valid image: {$valid}");
        $this->line("Products using fallback: {$fallback}");
        $this->line("Products with mismatched image: {$mismatched}");
        $this->line("Products missing image: {$missing}");

        return self::SUCCESS;
    }

    private function matchesMappingPath(string $path, string $petType, string $categoryType): bool
    {
        if (str_starts_with($path, '/products/fallback/')) {
            return true;
        }

        if ($petType !== '' && $categoryType !== '') {
            return str_starts_with($path, "/products/{$petType}/{$categoryType}/");
        }

        if ($categoryType !== '') {
            return str_contains($path, "/{$categoryType}/");
        }

        return str_starts_with($path, '/products/');
    }
}
