<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\ProductImageService;
use Illuminate\Console\Command;

class BackfillProductImagesCommand extends Command
{
    protected $signature = 'products:backfill-images {--limit=0} {--force : Overwrite existing image fields}';
    protected $description = 'Backfill product image_url/thumbnail_url/images and pet taxonomy using category-wise mapping';

    public function handle(ProductImageService $imageService): int
    {
        $limit = max(0, (int) $this->option('limit'));
        $force = (bool) $this->option('force');

        $query = Product::query()->with('category')->orderBy('id');
        if ($limit > 0) {
            $query->limit($limit);
        }

        $updated = 0;
        $skipped = 0;

        $query->chunkById(1000, function ($products) use ($imageService, $force, &$updated, &$skipped) {
            foreach ($products as $product) {
                $hasImage = !empty($product->image_url) || !empty($product->thumbnail_url) || !empty($product->images);
                if ($hasImage && !$force) {
                    $skipped++;
                    continue;
                }

                $petType = $imageService->inferPetTypeFromProduct($product);
                $categoryType = $imageService->inferCategoryFromProduct($product);
                $subCategory = $imageService->inferSubCategoryFromProduct($product);
                $images = $imageService->getMultipleImages($petType, $categoryType, $subCategory, 3);

                $product->forceFill([
                    'pet_type' => $petType ?: null,
                    'sub_category' => $subCategory ?: null,
                    'image_url' => $images[0] ?? null,
                    'thumbnail_url' => $images[0] ?? null,
                    'images' => $images,
                ])->save();

                $updated++;
            }
        });

        $this->info("Updated products: {$updated}");
        $this->line("Skipped products: {$skipped}");

        return self::SUCCESS;
    }
}

