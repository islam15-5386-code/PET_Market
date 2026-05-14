<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public const CATEGORY_MAP = [
        ['name' => 'Bird Supplies',   'slug' => 'bird-supplies',   'description' => 'Bird cages, food, toys and accessories',           'image_url' => '/products/bird/food/seed-mix/bird-seed-mix-1.jpg'],
        ['name' => 'Cat Food',        'slug' => 'cat-food',        'description' => 'Cat food, snacks, and nutrition products',          'image_url' => '/products/cat/food/dry-food/cat-dry-food-1.jpg'],
        ['name' => 'Collars & Leads', 'slug' => 'collars-leads',   'description' => 'Collars, leashes, harnesses and ID tags',            'image_url' => '/products/dog/accessories/collar/dog-collar-1.jpg'],
        ['name' => 'Dog Food',        'slug' => 'dog-food',        'description' => 'All types of dog food, treats and supplements',      'image_url' => '/products/dog/food/dry-food/dog-dry-food-1.jpg'],
        ['name' => 'Fish & Aquatics', 'slug' => 'fish-aquatics',   'description' => 'Aquariums, fish food, pumps and water care',          'image_url' => '/products/fish/accessories/aquarium/fish-aquarium-1.jpg'],
        ['name' => 'Pet Beds',        'slug' => 'pet-beds',        'description' => 'Beds, crates and sleeping accessories',                'image_url' => '/products/dog/accessories/bed/dog-bed-1.jpg'],
        ['name' => 'Pet Grooming',    'slug' => 'pet-grooming',    'description' => 'Shampoos, brushes and grooming tools',                 'image_url' => '/products/dog/grooming/brush/dog-brush-1.jpg'],
        ['name' => 'Pet Health',      'slug' => 'pet-health',      'description' => 'Vitamins, flea treatments and health products',        'image_url' => '/products/dog/medicine/vitamin/dog-vitamin-1.jpg'],
        ['name' => 'Pet Toys',        'slug' => 'pet-toys',        'description' => 'Toys and enrichment for all pet types',                'image_url' => '/products/dog/toys/chew-toy/dog-chew-toy-1.jpg'],
        ['name' => 'Small Animals',   'slug' => 'small-animals',   'description' => 'Supplies for rabbits, hamsters, guinea pigs',          'image_url' => '/products/rabbit/accessories/cage/rabbit-cage-1.jpg'],
    ];

    public function run(): void
    {
        foreach (self::CATEGORY_MAP as $cat) {
            Category::updateOrCreate(
                ['slug' => $cat['slug']],
                [
                    'name' => $cat['name'],
                    'description' => $cat['description'],
                    'image_url' => $cat['image_url'],
                ]
            );
        }

        $this->command->info('10 fixed categories seeded with image_url.');
    }
}
