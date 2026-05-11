<?php

namespace App\Console\Commands;

use App\Models\Category;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class SeedMarketplaceProductsCommand extends Command
{
    protected $signature = 'seed:marketplace-products {--chunk=5000} {--fresh : Truncate products before seed}';
    protected $description = 'Seed 1,000,000 marketplace products with chunked bulk inserts';

    private const TARGET = [
        'dog-food' => 180000,
        'cat-food' => 160000,
        'pet-health' => 120000,
        'pet-toys' => 110000,
        'pet-grooming' => 100000,
        'fish-aquatics' => 90000,
        'collars-leads' => 90000,
        'pet-beds' => 70000,
        'small-animals' => 50000,
        'bird-supplies' => 30000,
    ];

    private const LOCATIONS = [
        'Dhaka', 'Mirpur', 'Dhanmondi', 'Uttara', 'Banani', 'Gulshan', 'Mohammadpur',
        'Chattogram', 'Sylhet', 'Khulna', 'Rajshahi', 'Barishal', 'Cumilla', 'Narayanganj',
    ];

    private const BRAND_POOL = [
        'PawsPro', 'VetCare', 'PetNest', 'HappyPaws', 'AquaLife', 'WhiskerWorld',
        'Purrfect', 'BarkPlus', 'Petmate', 'TailBliss', 'FurGlow', 'Zoofresh',
    ];

    private const PRICE_RANGE = [
        'dog-food' => [350, 6500],
        'cat-food' => [250, 5000],
        'pet-health' => [150, 3500],
        'pet-toys' => [100, 2500],
        'pet-grooming' => [150, 3000],
        'fish-aquatics' => [100, 8000],
        'collars-leads' => [120, 2500],
        'pet-beds' => [500, 7000],
        'small-animals' => [100, 3000],
        'bird-supplies' => [80, 2500],
    ];

    public function handle(): int
    {
        $chunkSize = max(10, min((int) $this->option('chunk'), 10000));
        if (DB::getDriverName() === 'sqlite') {
            // SQLite has ~999 SQL variable limit; 15 fields/row => safe upper bound ~66 rows/batch.
            $chunkSize = min($chunkSize, 60);
        }
        $categories = Category::query()
            ->whereIn('slug', array_keys(self::TARGET))
            ->get()
            ->keyBy('slug');

        if ($categories->count() !== 10) {
            $this->error('Missing required categories. Run: php artisan db:seed --class=CategorySeeder');
            return self::FAILURE;
        }

        if ($this->option('fresh')) {
            DB::table('products')->truncate();
            $this->warn('Products table truncated.');
        }

        foreach (self::TARGET as $slug => $target) {
            $category = $categories[$slug];
            $prefix = strtoupper(str_replace('-', '', $slug));
            $existing = DB::table('products')->where('sku', 'like', $prefix . '-%')->count();

            if ($existing >= $target) {
                $this->info("{$slug}: already complete ({$existing}/{$target})");
                continue;
            }

            $this->line("Seeding {$slug}: {$existing}/{$target}");
            $this->seedCategory($category->id, $slug, $prefix, $existing + 1, $target, $chunkSize);
        }

        $total = DB::table('products')->count();
        $this->info("Done. Total products: {$total}");

        return self::SUCCESS;
    }

    private function seedCategory(int $categoryId, string $categorySlug, string $prefix, int $start, int $target, int $chunkSize): void
    {
        $cursor = $start;

        while ($cursor <= $target) {
            $upper = min($cursor + $chunkSize - 1, $target);
            $rows = [];
            $now = now();

            for ($i = $cursor; $i <= $upper; $i++) {
                $rows[] = $this->buildRow($categoryId, $categorySlug, $prefix, $i, $now);
            }

            try {
                DB::transaction(function () use ($rows): void {
                    DB::table('products')->insertOrIgnore($rows);
                }, 1);
            } catch (Throwable $e) {
                $this->error("Insert failed at {$categorySlug} #{$cursor}-#{$upper}: {$e->getMessage()}");
                return;
            }

            if ($upper % 5000 === 0 || $upper === $target) {
                $this->line("  inserted up to #{$upper} for {$categorySlug}");
            }
            $cursor = $upper + 1;
        }
    }

    private function buildRow(int $categoryId, string $categorySlug, string $prefix, int $n, $now): array
    {
        [$min, $max] = self::PRICE_RANGE[$categorySlug];
        $name = $this->generateName($categorySlug, $n);
        $slug = Str::slug($name) . '-' . strtolower($prefix) . '-' . $n;
        $sku = "{$prefix}-" . str_pad((string) $n, 7, '0', STR_PAD_LEFT);
        $price = number_format(mt_rand($min * 100, $max * 100) / 100, 2, '.', '');
        $stock = mt_rand(0, 500);
        $isAvailable = $stock > 0;
        $rating = number_format(mt_rand(30, 50) / 10, 1, '.', '');
        $reviewCount = mt_rand(0, 5000);

        return [
            'category_id' => $categoryId,
            'name' => $name,
            'slug' => $slug,
            'description' => $this->generateDescription($categorySlug, $name),
            'price' => $price,
            'stock_quantity' => $stock,
            'images' => json_encode([$this->categoryImage($categorySlug)]),
            'location' => self::LOCATIONS[array_rand(self::LOCATIONS)],
            'brand' => self::BRAND_POOL[array_rand(self::BRAND_POOL)],
            'sku' => $sku,
            'rating' => $rating,
            'review_count' => $reviewCount,
            'is_available' => $isAvailable,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    private function generateName(string $slug, int $n): string
    {
        $packs = [1, 2, 5, 10, 12];
        $kg = [1, 1.2, 2, 3, 5, 10];
        $pack = $packs[$n % count($packs)];
        $weight = $kg[$n % count($kg)];

        return match ($slug) {
            'dog-food' => ['Premium Chicken Dog Food', 'Grain Free Puppy Food', 'Beef Flavor Adult Dog Meal'][$n % 3] . " {$weight}kg",
            'cat-food' => ['Tuna Cat Food', 'Kitten Dry Food Chicken Flavor', 'Salmon Wet Cat Food Pack'][$n % 3] . " {$pack}pcs",
            'pet-health' => ['Pet Vitamin Supplement', 'Flea & Tick Protection Spray', 'Digestive Care Drops for Pets'][$n % 3] . " {$pack}pcs",
            'pet-toys' => ['Rubber Chew Toy', 'Interactive Cat Ball', 'Rope Toy for Dogs'][$n % 3] . " Model {$n}",
            'pet-grooming' => ['Pet Grooming Brush', 'Anti-Shedding Comb', 'Pet Shampoo Mild Formula'][$n % 3] . " {$pack}pcs",
            'fish-aquatics' => ['Aquarium Water Filter', 'Fish Food Flakes', 'Aquarium Decoration Stone'][$n % 3] . " {$pack}pcs",
            'collars-leads' => ['Adjustable Dog Collar', 'Nylon Pet Leash', 'Reflective Collar for Pets'][$n % 3] . " Size " . ['S', 'M', 'L', 'XL'][$n % 4],
            'pet-beds' => ['Soft Washable Pet Bed', 'Premium Cat Sleeping Bed', 'Waterproof Dog Mattress'][$n % 3] . " {$weight}ft",
            'small-animals' => ['Rabbit Food Mix', 'Hamster Cage Accessory', 'Guinea Pig Hay Pack'][$n % 3] . " {$pack}pcs",
            default => ['Bird Seed Mix', 'Parrot Feeding Bowl', 'Bird Cage Swing Toy'][$n % 3] . " {$pack}pcs",
        };
    }

    private function generateDescription(string $slug, string $name): string
    {
        return "{$name} for {$slug}. Quality checked, suitable for daily pet care, and sourced for Bangladesh marketplace customers.";
    }

    private function categoryImage(string $slug): string
    {
        return match ($slug) {
            'dog-food' => 'https://images.unsplash.com/photo-1583512603805-3cc6b41f3edb?auto=format&fit=crop&w=900&q=80',
            'cat-food' => 'https://images.unsplash.com/photo-1548767797-d8c844163c4c?auto=format&fit=crop&w=900&q=80',
            'pet-health' => 'https://images.unsplash.com/photo-1628009368231-7bb7cfcb0def?auto=format&fit=crop&w=900&q=80',
            'pet-toys' => 'https://images.unsplash.com/photo-1568640347023-a616a30bc3bd?auto=format&fit=crop&w=900&q=80',
            'pet-grooming' => 'https://images.unsplash.com/photo-1522069169874-c58ec4b76be5?auto=format&fit=crop&w=900&q=80',
            'fish-aquatics' => 'https://images.unsplash.com/photo-1474511320723-9a56873867b5?auto=format&fit=crop&w=900&q=80',
            'collars-leads' => 'https://images.unsplash.com/photo-1450778869180-41d0601e046e?auto=format&fit=crop&w=900&q=80',
            'pet-beds' => 'https://images.unsplash.com/photo-1548199973-03cce0bbc87b?auto=format&fit=crop&w=900&q=80',
            'small-animals' => 'https://images.unsplash.com/photo-1585110396000-c9ffd4e4b308?auto=format&fit=crop&w=900&q=80',
            default => 'https://images.unsplash.com/photo-1522926193341-e9ffd686c60f?auto=format&fit=crop&w=900&q=80',
        };
    }
}
