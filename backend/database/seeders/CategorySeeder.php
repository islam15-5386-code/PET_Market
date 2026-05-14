<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public const CATEGORY_MAP = [
        ['name' => 'Bird Supplies',   'slug' => 'bird-supplies',   'description' => 'Bird cages, food, toys and accessories',           'image_url' => 'https://images.unsplash.com/photo-1444464666168-49d633b86797?auto=format&fit=crop&w=1200&q=80'],
        ['name' => 'Cat Food',        'slug' => 'cat-food',        'description' => 'Cat food, snacks, and nutrition products',          'image_url' => 'https://images.unsplash.com/photo-1548767797-d8c844163c4c?auto=format&fit=crop&w=1200&q=80'],
        ['name' => 'Collars & Leads', 'slug' => 'collars-leads',   'description' => 'Collars, leashes, harnesses and ID tags',            'image_url' => 'https://images.unsplash.com/photo-1450778869180-41d0601e046e?auto=format&fit=crop&w=1200&q=80'],
        ['name' => 'Dog Food',        'slug' => 'dog-food',        'description' => 'All types of dog food, treats and supplements',      'image_url' => 'https://images.unsplash.com/photo-1583512603805-3cc6b41f3edb?auto=format&fit=crop&w=1200&q=80'],
        ['name' => 'Fish & Aquatics', 'slug' => 'fish-aquatics',   'description' => 'Aquariums, fish food, pumps and water care',          'image_url' => 'https://images.unsplash.com/photo-1474511320723-9a56873867b5?auto=format&fit=crop&w=1200&q=80'],
        ['name' => 'Pet Beds',        'slug' => 'pet-beds',        'description' => 'Beds, crates and sleeping accessories',                'image_url' => 'https://images.unsplash.com/photo-1548199973-03cce0bbc87b?auto=format&fit=crop&w=1200&q=80'],
        ['name' => 'Pet Grooming',    'slug' => 'pet-grooming',    'description' => 'Shampoos, brushes and grooming tools',                 'image_url' => 'https://images.unsplash.com/photo-1516734212186-a967f81ad0d7?auto=format&fit=crop&w=1200&q=80'],
        ['name' => 'Pet Health',      'slug' => 'pet-health',      'description' => 'Vitamins, flea treatments and health products',        'image_url' => 'https://images.unsplash.com/photo-1628009368231-7bb7cfcb0def?auto=format&fit=crop&w=1200&q=80'],
        ['name' => 'Pet Toys',        'slug' => 'pet-toys',        'description' => 'Toys and enrichment for all pet types',                'image_url' => 'https://images.unsplash.com/photo-1568640347023-a616a30bc3bd?auto=format&fit=crop&w=1200&q=80'],
        ['name' => 'Small Animals',   'slug' => 'small-animals',   'description' => 'Supplies for rabbits, hamsters, guinea pigs',          'image_url' => 'https://images.unsplash.com/photo-1585110396000-c9ffd4e4b308?auto=format&fit=crop&w=1200&q=80'],
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
