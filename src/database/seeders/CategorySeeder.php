<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Dog Food',       'icon' => '🦴', 'description' => 'All types of dog food, treats and supplements'],
            ['name' => 'Cat Food',        'icon' => '🐟', 'description' => 'Cat food, snacks, and nutrition products'],
            ['name' => 'Bird Supplies',   'icon' => '🐦', 'description' => 'Bird cages, food, toys and accessories'],
            ['name' => 'Fish & Aquatics', 'icon' => '🐠', 'description' => 'Aquariums, fish food, pumps and water care'],
            ['name' => 'Pet Grooming',    'icon' => '✂️',  'description' => 'Shampoos, brushes and grooming tools'],
            ['name' => 'Pet Toys',        'icon' => '🎾', 'description' => 'Toys and enrichment for all pet types'],
            ['name' => 'Pet Health',      'icon' => '💊', 'description' => 'Vitamins, flea treatments and health products'],
            ['name' => 'Collars & Leads', 'icon' => '🔗', 'description' => 'Collars, leashes, harnesses and ID tags'],
            ['name' => 'Pet Beds',        'icon' => '🛏️', 'description' => 'Beds, crates and sleeping accessories'],
            ['name' => 'Small Animals',   'icon' => '🐹', 'description' => 'Supplies for rabbits, hamsters, guinea pigs'],
        ];

        foreach ($categories as $cat) {
            Category::firstOrCreate(
                ['slug' => Str::slug($cat['name'])],
                ['name' => $cat['name'], 'icon' => $cat['icon'], 'description' => $cat['description']]
            );
        }

        $this->command->info('✅ ' . count($categories) . ' categories seeded.');
    }
}
