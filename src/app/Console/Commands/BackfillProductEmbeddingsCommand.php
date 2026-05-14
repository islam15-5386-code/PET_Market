<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class BackfillProductEmbeddingsCommand extends Command
{
    protected $signature = 'ai:backfill-product-embeddings {--chunk=500} {--limit=0} {--force : Recompute even when embedding already exists}';
    protected $description = 'Generate and store pgvector embeddings for products using FastAPI embedding endpoint';

    public function handle(): int
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->error('This command requires PostgreSQL with pgvector.');
            return self::FAILURE;
        }

        $chunk = max(50, min((int) $this->option('chunk'), 2000));
        $limit = max(0, (int) $this->option('limit'));
        $force = (bool) $this->option('force');

        $baseUrl = rtrim(config('services.ai_service.url', env('AI_SERVICE_URL', 'http://127.0.0.1:8001')), '/');

        $query = Product::query()->select(['id', 'name', 'description', 'brand']);
        if (!$force) {
            $query->whereNull('embedding');
        }
        $total = (clone $query)->count();
        $target = $limit > 0 ? min($limit, $total) : $total;
        if ($total === 0) {
            $this->info('No products require embedding backfill.');
            return self::SUCCESS;
        }

        $this->info("Backfilling embeddings for {$target} products...");
        $processed = 0;

        $query->orderBy('id')->chunkById($chunk, function ($products) use ($baseUrl, &$processed, $target) {
            foreach ($products as $product) {
                if ($processed >= $target) {
                    return false;
                }

                $text = trim(implode(' ', array_filter([
                    $product->name,
                    $product->brand,
                    $product->description,
                ])));

                if ($text === '') {
                    continue;
                }

                $resp = Http::timeout(30)->post("{$baseUrl}/ai/embeddings", ['text' => $text]);
                if (!$resp->successful()) {
                    $this->warn("Embedding failed for product #{$product->id}");
                    continue;
                }

                $vector = $resp->json('vector', []);
                if (!is_array($vector) || count($vector) !== 384) {
                    $this->warn("Invalid embedding dimension for product #{$product->id}");
                    continue;
                }

                $vectorLiteral = '[' . implode(',', array_map(static fn ($v) => number_format((float) $v, 8, '.', ''), $vector)) . ']';
                DB::update(
                    'UPDATE products SET embedding = ?::vector, updated_at = NOW() WHERE id = ?',
                    [$vectorLiteral, $product->id]
                );

                $processed++;
                if ($processed % 200 === 0) {
                    $this->line("Processed {$processed}/{$target}");
                }
            }
        }) ;

        $this->info("Done. Embedded {$processed} products.");
        return self::SUCCESS;
    }
}
