<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Services\ProductImageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class SeedMarketplaceProductsCommand extends Command
{
    private ProductImageService $imageService;

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

    private const NAME_TEMPLATES = [
        'dog-food' => [
            'Puppy Chicken Kibble', 'Adult Dog Dry Food', 'Lamb & Rice Dog Meal',
            'Grain Free Dog Kibble', 'Senior Dog Nutrition Formula', 'Beef Flavor Dog Food',
        ],
        'cat-food' => [
            'Kitten Tuna Dry Food', 'Adult Cat Salmon Formula', 'Chicken Cat Kibble',
            'Indoor Cat Hairball Control Food', 'Cat Wet Food Pouch', 'Kitten Growth Formula',
        ],
        'pet-health' => [
            'Pet Multivitamin Tablets', 'Deworming Support for Pets', 'Skin & Coat Omega Drops',
            'Digestive Probiotic Powder', 'Joint Care Supplement', 'Flea & Tick Control Spray',
        ],
        'pet-toys' => [
            'Interactive Cat Teaser Toy', 'Dog Chew Rope Toy', 'Squeaky Ball Toy',
            'Treat Dispensing Puzzle Toy', 'Rubber Chew Bone', 'Feather Wand Cat Toy',
        ],
        'pet-grooming' => [
            'Anti-Shedding Grooming Brush', 'Pet Shampoo Gentle Formula', 'Nail Clipper for Pets',
            'Pet Ear Cleaning Wipes', 'Detangling Comb for Pets', 'Pet Coat Conditioning Spray',
        ],
        'fish-aquatics' => [
            'Aquarium Fish Food Flakes', 'Aquarium Internal Filter', 'Water Conditioner for Fish Tank',
            'Aquarium Air Pump Kit', 'Fish Tank Decorative Pebbles', 'Aquarium Thermometer',
        ],
        'collars-leads' => [
            'Adjustable Dog Collar', 'Reflective Pet Leash', 'No-Pull Harness for Dogs',
            'Soft Nylon Cat Collar', 'Heavy Duty Dog Lead', 'ID Tag Collar Set',
        ],
        'pet-beds' => [
            'Orthopedic Dog Bed', 'Washable Cat Sleeping Bed', 'Donut Calming Pet Bed',
            'Waterproof Pet Mattress', 'Soft Plush Pet Cushion', 'Raised Cooling Pet Bed',
        ],
        'small-animals' => [
            'Rabbit Pellet Food', 'Hamster Bedding Pack', 'Guinea Pig Hay Blend',
            'Rabbit Chew Sticks', 'Small Animal Water Bottle', 'Hamster Tunnel Toy',
        ],
        'bird-supplies' => [
            'Bird Seed Nutrition Mix', 'Parrot Feeding Bowl', 'Bird Cage Perch Set',
            'Cuttlefish Bone for Birds', 'Canary Millet Spray', 'Bird Cage Swing Toy',
        ],
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
        $this->imageService = app(ProductImageService::class);
        $chunkSize = max(10, min((int) $this->option('chunk'), 10000));
        if (DB::getDriverName() === 'pgsql') {
            // PostgreSQL bind parameter limit is 65535; products insert uses 15 fields/row.
            $chunkSize = min($chunkSize, 4000);
        }
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
        $petType = $this->imageService->normalizePetType("{$categorySlug} {$name}");
        $categoryType = $this->imageService->normalizeCategory($categorySlug);
        $subCategory = $this->imageService->normalizeSubCategory($name);
        $images = $this->imageService->getMultipleImages($petType, $categoryType, $subCategory, 3);
        $slug = Str::slug($name) . '-' . strtolower($prefix) . '-' . $n;
        $sku = "{$prefix}-" . str_pad((string) $n, 7, '0', STR_PAD_LEFT);
        $price = number_format(mt_rand($min * 100, $max * 100) / 100, 2, '.', '');
        $stock = mt_rand(0, 500);
        $isAvailable = $stock > 0;
        $rating = number_format(mt_rand(30, 50) / 10, 1, '.', '');
        $reviewCount = mt_rand(0, 5000);

        return [
            'category_id' => $categoryId,
            'pet_type' => $petType ?: null,
            'sub_category' => $subCategory ?: null,
            'name' => $name,
            'slug' => $slug,
            'description' => $this->generateDescription($categorySlug, $name),
            'price' => $price,
            'stock_quantity' => $stock,
            'images' => json_encode($images),
            'image_url' => $images[0] ?? null,
            'thumbnail_url' => $images[0] ?? null,
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
        $packs = [1, 2, 3, 5, 10, 12];
        $kg = [0.5, 1, 1.5, 2, 3, 5, 10];
        $ml = [100, 200, 250, 300, 500];
        $sizes = ['XS', 'S', 'M', 'L', 'XL'];
        $base = self::NAME_TEMPLATES[$slug][$n % count(self::NAME_TEMPLATES[$slug])];

        return match ($slug) {
            'dog-food', 'cat-food', 'small-animals' => "{$base} {$kg[$n % count($kg)]}kg",
            'pet-health' => str_contains($base, 'Spray') || str_contains($base, 'Drops')
                ? "{$base} {$ml[$n % count($ml)]}ml"
                : "{$base} {$packs[$n % count($packs)]}pcs",
            'pet-toys' => "{$base} Model " . (($n % 5000) + 1),
            'pet-grooming' => str_contains($base, 'Shampoo') || str_contains($base, 'Spray')
                ? "{$base} {$ml[$n % count($ml)]}ml"
                : "{$base} {$packs[$n % count($packs)]}pcs",
            'fish-aquatics' => str_contains($base, 'Food')
                ? "{$base} {$kg[$n % count($kg)]}kg"
                : "{$base} {$packs[$n % count($packs)]}pcs",
            'collars-leads' => "{$base} Size {$sizes[$n % count($sizes)]}",
            'pet-beds' => "{$base} " . [18, 22, 26, 30, 36, 42][$n % 6] . " inch",
            default => "{$base} {$packs[$n % count($packs)]}pcs",
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
