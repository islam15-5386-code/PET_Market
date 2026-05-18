<?php

namespace App\Console\Commands;

use App\Models\Category;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class VerifyMarketplaceSeedCommand extends Command
{
    protected $signature = 'verify:marketplace-seed';
    protected $description = 'Verify category/product counts and filterability for marketplace seed';

    private const EXPECTED = [
        'dog-food' => 150000,
        'cat-food' => 150000,
        'bird-supplies' => 100000,
        'fish-aquatics' => 100000,
        'pet-grooming' => 100000,
        'pet-health' => 100000,
        'pet-toys' => 100000,
        'collars-leads' => 70000,
        'pet-beds' => 70000,
        'small-animals' => 60000,
    ];

    public function handle(): int
    {
        $ok = true;
        $categories = Category::query()->whereIn('slug', array_keys(self::EXPECTED))->get()->keyBy('slug');

        if ($categories->count() !== 10) {
            $this->error('Category count mismatch. Expected 10.');
            $ok = false;
        }

        $missingImage = Category::query()
            ->whereIn('slug', array_keys(self::EXPECTED))
            ->where(function ($q): void {
                $q->whereNull('image_url')->orWhere('image_url', '');
            })
            ->count();
        if ($missingImage > 0) {
            $this->error("{$missingImage} categories are missing image_url.");
            $ok = false;
        }

        $total = DB::table('products')->count();
        if ($total !== 1000000) {
            $this->error("Total products mismatch. Expected 1000000, got {$total}.");
            $ok = false;
        }

        foreach (self::EXPECTED as $slug => $expected) {
            if (!isset($categories[$slug])) {
                $this->error("Category missing: {$slug}");
                $ok = false;
                continue;
            }
            $count = DB::table('products')->where('category_id', $categories[$slug]->id)->count();
            $this->line("{$slug}: {$count}");
            if ($count !== $expected) {
                $this->error("Count mismatch for {$slug}. Expected {$expected}, got {$count}.");
                $ok = false;
            }
        }

        $sampleSearch = DB::table('products')->where('name', 'like', '%Dog Food%')->limit(1)->exists();
        $sampleLocation = DB::table('products')->where('location', 'Dhaka')->limit(1)->exists();
        $samplePriceRange = DB::table('products')->whereBetween('price', [1000, 2000])->limit(1)->exists();

        $this->line('Search sample: ' . ($sampleSearch ? 'ok' : 'fail'));
        $this->line('Location sample: ' . ($sampleLocation ? 'ok' : 'fail'));
        $this->line('Price range sample: ' . ($samplePriceRange ? 'ok' : 'fail'));

        if (!$sampleSearch || !$sampleLocation || !$samplePriceRange) {
            $ok = false;
        }

        $this->info($ok ? 'Marketplace seed verification passed.' : 'Marketplace seed verification failed.');
        return $ok ? self::SUCCESS : self::FAILURE;
    }
}
