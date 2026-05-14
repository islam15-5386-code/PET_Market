<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

class VerifyAiDetailsCommand extends Command
{
    protected $signature = 'products:verify-ai-details {--limit=0}';
    protected $description = 'Verify product AI detail fields coverage';

    public function handle(): int
    {
        $limit = max(0, (int) $this->option('limit'));
        $query = Product::query()->orderBy('id');
        if ($limit > 0) {
            $query->limit($limit);
        }

        $total = $query->count();
        if ($total === 0) {
            $this->warn('No products found.');
            return self::SUCCESS;
        }

        $withShort = Product::query()->whereNotNull('ai_generated_short_description')->count();
        $withLong = Product::query()->whereNotNull('ai_generated_long_description')->count();
        $withMeta = Product::query()->whereNotNull('ai_meta_title')->count();
        $withTags = Product::query()->whereNotNull('ai_generated_tags')->count();

        $this->line("Total products: {$total}");
        $this->line("With AI short description: {$withShort}");
        $this->line("With AI long description: {$withLong}");
        $this->line("With AI meta title: {$withMeta}");
        $this->line("With AI tags: {$withTags}");

        return self::SUCCESS;
    }
}

