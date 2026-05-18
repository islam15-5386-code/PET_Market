<?php

namespace App\Console\Commands;

use App\Models\Category;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SeedMarketplaceProductsCommand extends Command
{
    protected $signature = 'marketplace:seed-products
        {--count=1000 : Total products to seed}
        {--batch=500 : Batch size for chunk insert}
        {--fresh : Truncate products table before seeding}';

    protected $aliases = ['seed:marketplace-products'];

    protected $description = 'Seed marketplace products with category-wise realistic data and image URLs (supports up to 1M rows).';

    private const DISTRIBUTION = [
        'dog-food' => 0.15,
        'cat-food' => 0.15,
        'bird-supplies' => 0.10,
        'fish-aquatics' => 0.10,
        'pet-grooming' => 0.10,
        'pet-health' => 0.10,
        'pet-toys' => 0.10,
        'collars-leads' => 0.07,
        'pet-beds' => 0.07,
        'small-animals' => 0.06,
    ];

    private const LOCATIONS = [
        'Dhaka', 'Chattogram', 'Sylhet', 'Rajshahi', 'Khulna',
        'Barishal', 'Rangpur', 'Mymensingh', 'Narayanganj', 'Gazipur',
    ];

    private const PRICE_RANGES = [
        'dog-food' => [300, 5000],
        'cat-food' => [300, 5000],
        'bird-supplies' => [100, 6000],
        'fish-aquatics' => [150, 12000],
        'pet-grooming' => [250, 2500],
        'pet-health' => [200, 3000],
        'pet-toys' => [150, 2000],
        'collars-leads' => [200, 3500],
        'pet-beds' => [800, 8000],
        'small-animals' => [200, 5000],
    ];

    private const PET_TYPE = [
        'dog-food' => 'Dog',
        'cat-food' => 'Cat',
        'bird-supplies' => 'Bird',
        'fish-aquatics' => 'Fish',
        'pet-grooming' => 'Mixed',
        'pet-health' => 'Mixed',
        'pet-toys' => 'Mixed',
        'collars-leads' => 'Dog',
        'pet-beds' => 'Mixed',
        'small-animals' => 'Small Animal',
    ];

    private const AGE_GROUPS = ['Puppy/Kitten', 'Junior', 'Adult', 'Senior', 'All Ages'];

    private const BRANDS = [
        'dog-food' => ['Royal Canin', 'Pedigree', 'Acana', 'Orijen', 'Taste of the Wild'],
        'cat-food' => ['Whiskas', 'Meow Mix', 'Royal Canin', 'Purina', 'Sheba'],
        'bird-supplies' => ['Versele-Laga', 'Vitakraft', 'Kaytee', 'BirdLife', 'PetNest'],
        'fish-aquatics' => ['Tetra', 'Fluval', 'Aquael', 'Sera', 'OceanFree'],
        'pet-grooming' => ['Wahl', 'Hertzko', 'Petkin', 'VetCare', 'FurEase'],
        'pet-health' => ['Frontline', 'Beaphar', 'Bayer', 'Virbac', 'VetLife'],
        'pet-toys' => ['Kong', 'Nylabone', 'Petstages', 'SmartyKat', 'PawsPlay'],
        'collars-leads' => ['Ruffwear', 'PetSafe', 'Trixie', 'BarkPro', 'LeashLab'],
        'pet-beds' => ['FurHaven', 'PetFusion', 'SleepyPaws', 'CozyNest', 'PawComfort'],
        'small-animals' => ['Supreme', 'Oxbow', 'Kaytee', 'Bunny Nature', 'TinyPets'],
    ];

    private const TAGS = [
        'dog-food' => ['dog-food', 'nutrition', 'dry-food', 'wet-food'],
        'cat-food' => ['cat-food', 'kitten-food', 'nutrition', 'wet-food'],
        'bird-supplies' => ['bird-cage', 'bird-food', 'parrot-toy', 'avian-care'],
        'fish-aquatics' => ['aquarium', 'fish-food', 'filter', 'aquatic-care'],
        'pet-grooming' => ['shampoo', 'brush', 'comb', 'grooming'],
        'pet-health' => ['supplement', 'flea-tick', 'vet-care', 'wellness'],
        'pet-toys' => ['toy', 'chew-toy', 'interactive', 'playtime'],
        'collars-leads' => ['collar', 'leash', 'harness', 'walking'],
        'pet-beds' => ['pet-bed', 'comfort', 'sleep', 'orthopedic'],
        'small-animals' => ['rabbit', 'hamster', 'guinea-pig', 'small-pet'],
    ];

    // Stable, direct image URLs by category and product type.
    private const IMAGE_POOL = [
        'dog-food' => [
            'https://images.unsplash.com/photo-1583512603805-3cc6b41f3edb?auto=format&fit=crop&w=1200&q=80',
            'https://images.pexels.com/photos/6568501/pexels-photo-6568501.jpeg?auto=compress&cs=tinysrgb&w=1200',
            'https://images.pexels.com/photos/7474355/pexels-photo-7474355.jpeg?auto=compress&cs=tinysrgb&w=1200',
            'https://images.unsplash.com/photo-1601758228041-f3b2795255f1?auto=format&fit=crop&w=1200&q=80',
        ],
        'cat-food' => [
            'https://images.unsplash.com/photo-1548767797-d8c844163c4c?auto=format&fit=crop&w=1200&q=80',
            'https://images.pexels.com/photos/617278/pexels-photo-617278.jpeg?auto=compress&cs=tinysrgb&w=1200',
            'https://images.unsplash.com/photo-1516750105099-4b8a83e217ee?auto=format&fit=crop&w=1200&q=80',
            'https://images.pexels.com/photos/1643457/pexels-photo-1643457.jpeg?auto=compress&cs=tinysrgb&w=1200',
        ],
        'bird-supplies' => [
            'https://images.pexels.com/photos/56733/canary-yellow-bird-bird-56733.jpeg?auto=compress&cs=tinysrgb&w=1200',
            'https://images.unsplash.com/photo-1444464666168-49d633b86797?auto=format&fit=crop&w=1200&q=80',
            'https://images.pexels.com/photos/1661179/pexels-photo-1661179.jpeg?auto=compress&cs=tinysrgb&w=1200',
            'https://images.unsplash.com/photo-1552728089-57bdde30beb3?auto=format&fit=crop&w=1200&q=80',
        ],
        'fish-aquatics' => [
            'https://images.unsplash.com/photo-1522069169874-c58ec4b76be5?auto=format&fit=crop&w=1200&q=80',
            'https://images.pexels.com/photos/128756/pexels-photo-128756.jpeg?auto=compress&cs=tinysrgb&w=1200',
            'https://images.unsplash.com/photo-1474511320723-9a56873867b5?auto=format&fit=crop&w=1200&q=80',
            'https://images.pexels.com/photos/213399/pexels-photo-213399.jpeg?auto=compress&cs=tinysrgb&w=1200',
        ],
        'pet-grooming' => [
            'https://images.unsplash.com/photo-1516734212186-a967f81ad0d7?auto=format&fit=crop&w=1200&q=80',
            'https://images.pexels.com/photos/5732417/pexels-photo-5732417.jpeg?auto=compress&cs=tinysrgb&w=1200',
            'https://images.unsplash.com/photo-1517849845537-4d257902454a?auto=format&fit=crop&w=1200&q=80',
            'https://images.pexels.com/photos/6568944/pexels-photo-6568944.jpeg?auto=compress&cs=tinysrgb&w=1200',
        ],
        'pet-health' => [
            'https://images.unsplash.com/photo-1628009368231-7bb7cfcb0def?auto=format&fit=crop&w=1200&q=80',
            'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?auto=format&fit=crop&w=1200&q=80',
            'https://images.pexels.com/photos/593451/pexels-photo-593451.jpeg?auto=compress&cs=tinysrgb&w=1200',
            'https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?auto=format&fit=crop&w=1200&q=80',
        ],
        'pet-toys' => [
            'https://images.unsplash.com/photo-1568640347023-a616a30bc3bd?auto=format&fit=crop&w=1200&q=80',
            'https://images.pexels.com/photos/4587995/pexels-photo-4587995.jpeg?auto=compress&cs=tinysrgb&w=1200',
            'https://images.unsplash.com/photo-1537151625747-768eb6cf92b2?auto=format&fit=crop&w=1200&q=80',
            'https://images.pexels.com/photos/7210268/pexels-photo-7210268.jpeg?auto=compress&cs=tinysrgb&w=1200',
        ],
        'collars-leads' => [
            'https://images.unsplash.com/photo-1450778869180-41d0601e046e?auto=format&fit=crop&w=1200&q=80',
            'https://images.pexels.com/photos/1904105/pexels-photo-1904105.jpeg?auto=compress&cs=tinysrgb&w=1200',
            'https://images.unsplash.com/photo-1558944351-cb5e9f3c0c36?auto=format&fit=crop&w=1200&q=80',
            'https://images.pexels.com/photos/6568953/pexels-photo-6568953.jpeg?auto=compress&cs=tinysrgb&w=1200',
        ],
        'pet-beds' => [
            'https://images.unsplash.com/photo-1548199973-03cce0bbc87b?auto=format&fit=crop&w=1200&q=80',
            'https://images.pexels.com/photos/7210748/pexels-photo-7210748.jpeg?auto=compress&cs=tinysrgb&w=1200',
            'https://images.pexels.com/photos/5865201/pexels-photo-5865201.jpeg?auto=compress&cs=tinysrgb&w=1200',
            'https://images.pexels.com/photos/7690148/pexels-photo-7690148.jpeg?auto=compress&cs=tinysrgb&w=1200',
        ],
        'small-animals' => [
            'https://images.unsplash.com/photo-1585110396000-c9ffd4e4b308?auto=format&fit=crop&w=1200&q=80',
            'https://images.pexels.com/photos/326012/pexels-photo-326012.jpeg?auto=compress&cs=tinysrgb&w=1200',
            'https://images.pexels.com/photos/733416/pexels-photo-733416.jpeg?auto=compress&cs=tinysrgb&w=1200',
            'https://images.pexels.com/photos/104373/pexels-photo-104373.jpeg?auto=compress&cs=tinysrgb&w=1200',
        ],
    ];

    private const NAME_TEMPLATES = [
        'dog-food' => [
            'Royal Canin Puppy Dry Food %s',
            'Pedigree Adult Dog Food %s',
            'Grain-Free Chicken Dog Meal %s',
            'Senior Dog Lamb Formula %s',
        ],
        'cat-food' => [
            'Whiskas Kitten Tuna %s',
            'Meow Mix Indoor Cat Food %s',
            'Salmon Wet Cat Food Pack %s',
            'Hairball Control Cat Formula %s',
        ],
        'bird-supplies' => [
            'Bird Seed Mix %s',
            'Parrot Toy Set %s',
            'Steel Bird Cage Medium %s',
            'Bird Feeder Bowl Pack %s',
        ],
        'fish-aquatics' => [
            'Aquarium Filter Medium %s',
            'Fish Food Flakes %s',
            'Glass Fish Tank Kit %s',
            'Aquarium Gravel & Decor %s',
        ],
        'pet-grooming' => [
            'Pet Grooming Shampoo %s',
            'Anti-Shed Grooming Brush %s',
            'Pet Nail Clipper Pro %s',
            'Detangling Pet Comb %s',
        ],
        'pet-health' => [
            'Tick & Flea Spray for Pets %s',
            'Pet Multivitamin Supplement %s',
            'Digestive Care Pet Drops %s',
            'Joint Support Pet Formula %s',
        ],
        'pet-toys' => [
            'Rubber Chew Toy %s',
            'Interactive Cat Ball %s',
            'Durable Rope Toy %s',
            'Squeaky Dog Toy %s',
        ],
        'collars-leads' => [
            'Adjustable Dog Collar %s',
            'Reflective Pet Leash %s',
            'Comfort Fit Pet Harness %s',
            'Nylon Training Lead %s',
        ],
        'pet-beds' => [
            'Soft Pet Bed Medium %s',
            'Orthopedic Dog Bed %s',
            'Round Cat Sleeping Bed %s',
            'Waterproof Pet Mattress %s',
        ],
        'small-animals' => [
            'Rabbit Food Mix %s',
            'Hamster Cage Accessory Kit %s',
            'Guinea Pig Hay Pack %s',
            'Rabbit Toy Bundle %s',
        ],
    ];

    private const SIZE_OPTIONS = [
        '500g', '1kg', '1.2kg', '2kg', '3kg', '5kg', '500ml', '750ml', 'Pack of 2', 'Pack of 4',
    ];

    public function handle(): int
    {
        $count = max(1, (int) $this->option('count'));
        $batch = max(100, min((int) $this->option('batch'), 10000));

        if (DB::getDriverName() === 'sqlite') {
            $batch = min($batch, 100);
        }
        if (DB::getDriverName() === 'pgsql') {
            // PostgreSQL bind parameter limit is 65535. Keep batch safely below that.
            // Each row currently inserts 19 columns -> 19 * 3000 = 57000 params.
            $safePgBatch = 3000;
            if ($batch > $safePgBatch) {
                $this->warn("Batch {$batch} exceeds PostgreSQL safe bind threshold; using {$safePgBatch}.");
                $batch = $safePgBatch;
            }
        }

        $this->info("Preparing to seed {$count} products with batch size {$batch}...");
        DB::connection()->disableQueryLog();

        $categories = Category::query()
            ->whereIn('slug', array_keys(self::DISTRIBUTION))
            ->get(['id', 'name', 'slug'])
            ->keyBy('slug');

        if ($categories->count() !== count(self::DISTRIBUTION)) {
            $this->warn('Some required categories are missing. Seeding categories now...');
            $this->call('db:seed', ['--class' => 'CategorySeeder', '--force' => true]);
            $categories = Category::query()
                ->whereIn('slug', array_keys(self::DISTRIBUTION))
                ->get(['id', 'name', 'slug'])
                ->keyBy('slug');
            if ($categories->count() !== count(self::DISTRIBUTION)) {
                $this->error('Required categories are still missing after CategorySeeder.');
                return self::FAILURE;
            }
        }

        if ($this->option('fresh')) {
            DB::table('products')->truncate();
            $this->warn('products table truncated due to --fresh');
        }

        $plan = $this->buildDistributionPlan($count);

        $globalSeeded = 0;
        $categoryInserted = [];
        $startedAt = microtime(true);

        foreach ($plan as $slug => $target) {
            if ($target <= 0) {
                continue;
            }

            $category = $categories[$slug];
            $existing = (int) DB::table('products')->where('category_id', $category->id)->count();
            $this->line("Seeding {$slug} target={$target} (existing={$existing})");

            $insertedForCategory = 0;
            $sequence = $existing + 1;

            while ($insertedForCategory < $target) {
                $take = min($batch, $target - $insertedForCategory);
                $rows = [];
                $now = now();

                for ($i = 0; $i < $take; $i++) {
                    $rows[] = $this->buildRow($category->id, $slug, $sequence++, $now);
                }

                DB::table('products')->insert($rows);

                $insertedForCategory += $take;
                $globalSeeded += $take;

                $this->line("Seeded {$globalSeeded} / {$count}");
                unset($rows);
            }
            $categoryInserted[$slug] = $insertedForCategory;
        }

        $seconds = max(1, (int) (microtime(true) - $startedAt));
        $rate = (int) floor($globalSeeded / $seconds);
        $totalInDb = (int) DB::table('products')->count();

        $this->info('Marketplace product seeding completed.');
        $this->info("Inserted this run: {$globalSeeded}");
        $this->info("Total products in DB: {$totalInDb}");
        $this->info("Elapsed: {$seconds}s ({$rate} rows/s)");
        $this->line('Category-wise inserted this run:');
        foreach ($categoryInserted as $slug => $inserted) {
            $this->line(" - {$slug}: {$inserted}");
        }

        return self::SUCCESS;
    }

    /** @return array<string, int> */
    private function buildDistributionPlan(int $count): array
    {
        $plan = [];
        $remainders = [];
        $allocated = 0;

        foreach (self::DISTRIBUTION as $slug => $percent) {
            $raw = $count * $percent;
            $qty = (int) floor($raw);
            $plan[$slug] = $qty;
            $remainders[$slug] = $raw - $qty;
            $allocated += $qty;
        }

        $remaining = $count - $allocated;
        if ($remaining > 0) {
            arsort($remainders);
            $keys = array_keys($remainders);
            for ($i = 0; $i < $remaining; $i++) {
                $plan[$keys[$i % count($keys)]]++;
            }
        }

        return $plan;
    }

    private function buildRow(int $categoryId, string $slug, int $sequence, $now): array
    {
        $nameTemplate = self::NAME_TEMPLATES[$slug][$sequence % count(self::NAME_TEMPLATES[$slug])];
        $size = self::SIZE_OPTIONS[$sequence % count(self::SIZE_OPTIONS)];
        $name = sprintf($nameTemplate, $size);

        $baseSlug = Str::slug($name);
        $uniquePart = str_pad((string) $sequence, 8, '0', STR_PAD_LEFT);
        $slugValue = "{$baseSlug}-{$slug}-{$uniquePart}";

        [$minPrice, $maxPrice] = self::PRICE_RANGES[$slug];
        $price = mt_rand($minPrice * 100, $maxPrice * 100) / 100;
        $stock = mt_rand(1, 500);
        $imageUrl = self::IMAGE_POOL[$slug][$sequence % count(self::IMAGE_POOL[$slug])];
        $brand = self::BRANDS[$slug][$sequence % count(self::BRANDS[$slug])];
        $petType = self::PET_TYPE[$slug];
        $age = self::AGE_GROUPS[$sequence % count(self::AGE_GROUPS)];
        $tagPool = self::TAGS[$slug];

        $tags = [
            $tagPool[0],
            $tagPool[1],
            strtolower(str_replace(' ', '-', $brand)),
            strtolower(str_replace(' ', '-', $age)),
        ];

        return [
            'category_id' => $categoryId,
            'name' => $name,
            'slug' => $slugValue,
            'description' => $this->descriptionFor($slug, $name, $brand, $petType),
            'price' => number_format($price, 2, '.', ''),
            'stock_quantity' => $stock,
            'images' => json_encode([$imageUrl]),
            'image_url' => $imageUrl,
            'location' => self::LOCATIONS[array_rand(self::LOCATIONS)],
            'brand' => $brand,
            'pet_type' => $petType,
            'age_group' => $age,
            'tags' => json_encode(array_values(array_unique($tags))),
            'sku' => strtoupper(substr(str_replace('-', '', $slug), 0, 10)) . '-' . $uniquePart,
            'rating' => number_format(mt_rand(35, 50) / 10, 2, '.', ''),
            'review_count' => mt_rand(0, 10000),
            'is_available' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    private function descriptionFor(string $slug, string $name, string $brand, string $petType): string
    {
        return match ($slug) {
            'dog-food', 'cat-food' => "{$name} by {$brand}. Balanced nutrition for {$petType} with quality protein and essential vitamins.",
            'bird-supplies' => "{$name} by {$brand}. Reliable bird care accessory designed for hygiene, enrichment, and everyday convenience.",
            'fish-aquatics' => "{$name} by {$brand}. Built for aquarium stability and healthy aquatic environments.",
            'pet-grooming' => "{$name} by {$brand}. Gentle grooming product for cleaner coat, healthier skin, and easier maintenance.",
            'pet-health' => "{$name} by {$brand}. Pet wellness support product developed for preventive care and daily vitality.",
            'pet-toys' => "{$name} by {$brand}. Engaging pet toy for exercise, enrichment, and stress relief.",
            'collars-leads' => "{$name} by {$brand}. Durable walking essential with comfort-focused design and secure handling.",
            'pet-beds' => "{$name} by {$brand}. Cozy pet sleeping solution with support and comfort for daily rest.",
            default => "{$name} by {$brand}. Practical small-animal care product suitable for routine habitat and feeding needs.",
        };
    }
}
