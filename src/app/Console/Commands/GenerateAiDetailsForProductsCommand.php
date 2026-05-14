<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\AiProductDescriptionService;
use Illuminate\Console\Command;

class GenerateAiDetailsForProductsCommand extends Command
{
    protected $signature = 'products:generate-ai-details {--limit=100} {--force}';
    protected $description = 'Generate AI details for products missing AI content';

    public function handle(AiProductDescriptionService $service): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $force = (bool) $this->option('force');

        $query = Product::query()->with('category')->orderBy('id');

        if (!$force) {
            $query->where(function ($q) {
                $q->whereNull('ai_generated_short_description')
                    ->orWhereNull('ai_generated_long_description')
                    ->orWhereNull('ai_meta_title');
            });
        }

        $products = $query->limit($limit)->get();
        if ($products->isEmpty()) {
            $this->info('No products matched for AI generation.');
            return self::SUCCESS;
        }

        $generated = 0;
        $failed = 0;

        foreach ($products as $product) {
            try {
                $payload = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'category' => $product->category?->name ?? 'Pet Supplies',
                    'pet_type' => $product->pet_type ?: 'Pet',
                    'age_group' => null,
                    'brand' => $product->brand,
                    'price' => (float) $product->price,
                    'weight_or_size' => null,
                    'ingredients_or_materials' => [],
                    'key_features' => [],
                    'usage_instruction' => null,
                    'safety_note' => null,
                    'target_customer' => null,
                    'language' => 'English',
                    'tone' => 'professional',
                ];

                $service->generate($payload, null);
                $generated++;
            } catch (\Throwable $e) {
                $failed++;
                $this->warn("Failed product #{$product->id}: {$e->getMessage()}");
            }
        }

        $this->info("AI details generated: {$generated}");
        $this->line("AI details failed: {$failed}");

        return self::SUCCESS;
    }
}

