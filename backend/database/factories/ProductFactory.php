<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

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

    private const PET_TYPES = [
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

    private const NAME_TEMPLATES = [
        'dog-food' => ['Royal Canin Puppy Dry Food %s', 'Pedigree Adult Dog Food %s'],
        'cat-food' => ['Whiskas Kitten Tuna %s', 'Meow Mix Indoor Cat Food %s'],
        'bird-supplies' => ['Bird Seed Mix %s', 'Steel Bird Cage Medium %s'],
        'fish-aquatics' => ['Aquarium Filter Medium %s', 'Fish Food Flakes %s'],
        'pet-grooming' => ['Pet Grooming Shampoo %s', 'Pet Nail Clipper Pro %s'],
        'pet-health' => ['Tick & Flea Spray for Pets %s', 'Pet Multivitamin Supplement %s'],
        'pet-toys' => ['Rubber Chew Toy %s', 'Interactive Cat Ball %s'],
        'collars-leads' => ['Adjustable Dog Collar %s', 'Reflective Pet Leash %s'],
        'pet-beds' => ['Soft Pet Bed Medium %s', 'Orthopedic Dog Bed %s'],
        'small-animals' => ['Rabbit Food Mix %s', 'Hamster Cage Accessory Kit %s'],
    ];

    private const IMAGE_POOL = [
        'dog-food' => [
            'https://images.unsplash.com/photo-1583512603805-3cc6b41f3edb?auto=format&fit=crop&w=1200&q=80',
            'https://images.pexels.com/photos/6568501/pexels-photo-6568501.jpeg?auto=compress&cs=tinysrgb&w=1200',
        ],
        'cat-food' => [
            'https://images.unsplash.com/photo-1548767797-d8c844163c4c?auto=format&fit=crop&w=1200&q=80',
            'https://images.pexels.com/photos/617278/pexels-photo-617278.jpeg?auto=compress&cs=tinysrgb&w=1200',
        ],
        'bird-supplies' => [
            'https://images.unsplash.com/photo-1444464666168-49d633b86797?auto=format&fit=crop&w=1200&q=80',
            'https://images.pexels.com/photos/1661179/pexels-photo-1661179.jpeg?auto=compress&cs=tinysrgb&w=1200',
        ],
        'fish-aquatics' => [
            'https://images.unsplash.com/photo-1474511320723-9a56873867b5?auto=format&fit=crop&w=1200&q=80',
            'https://images.pexels.com/photos/128756/pexels-photo-128756.jpeg?auto=compress&cs=tinysrgb&w=1200',
        ],
        'pet-grooming' => [
            'https://images.unsplash.com/photo-1516734212186-a967f81ad0d7?auto=format&fit=crop&w=1200&q=80',
            'https://images.pexels.com/photos/5732417/pexels-photo-5732417.jpeg?auto=compress&cs=tinysrgb&w=1200',
        ],
        'pet-health' => [
            'https://images.unsplash.com/photo-1628009368231-7bb7cfcb0def?auto=format&fit=crop&w=1200&q=80',
            'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?auto=format&fit=crop&w=1200&q=80',
        ],
        'pet-toys' => [
            'https://images.unsplash.com/photo-1568640347023-a616a30bc3bd?auto=format&fit=crop&w=1200&q=80',
            'https://images.pexels.com/photos/4587995/pexels-photo-4587995.jpeg?auto=compress&cs=tinysrgb&w=1200',
        ],
        'collars-leads' => [
            'https://images.unsplash.com/photo-1450778869180-41d0601e046e?auto=format&fit=crop&w=1200&q=80',
            'https://images.pexels.com/photos/1904105/pexels-photo-1904105.jpeg?auto=compress&cs=tinysrgb&w=1200',
        ],
        'pet-beds' => [
            'https://images.unsplash.com/photo-1548199973-03cce0bbc87b?auto=format&fit=crop&w=1200&q=80',
            'https://images.pexels.com/photos/7690148/pexels-photo-7690148.jpeg?auto=compress&cs=tinysrgb&w=1200',
        ],
        'small-animals' => [
            'https://images.unsplash.com/photo-1585110396000-c9ffd4e4b308?auto=format&fit=crop&w=1200&q=80',
            'https://images.pexels.com/photos/326012/pexels-photo-326012.jpeg?auto=compress&cs=tinysrgb&w=1200',
        ],
    ];

    private const SIZE_OPTIONS = ['500g', '1kg', '1.2kg', '2kg', '3kg', '500ml', 'Pack of 2'];

    public function definition(): array
    {
        $category = Category::query()->inRandomOrder()->first();
        $slug = $category?->slug ?? 'pet-toys';
        $slug = array_key_exists($slug, self::PRICE_RANGES) ? $slug : 'pet-toys';
        [$minPrice, $maxPrice] = self::PRICE_RANGES[$slug];
        $stock = $this->faker->numberBetween(0, 500);
        $size = $this->faker->randomElement(self::SIZE_OPTIONS);
        $template = $this->faker->randomElement(self::NAME_TEMPLATES[$slug]);
        $name = sprintf($template, $size);
        $imageUrl = $this->faker->randomElement(self::IMAGE_POOL[$slug]);

        return [
            'category_id' => $category?->id ?? Category::factory(),
            'name' => $name,
            'slug' => Str::slug($name . '-' . $this->faker->unique()->numberBetween(10000, 999999)),
            'description' => $this->faker->sentence(16),
            'price' => $this->faker->randomFloat(2, $minPrice, $maxPrice),
            'stock_quantity' => $stock,
            'images' => [$imageUrl],
            'image_url' => $imageUrl,
            'location' => $this->faker->randomElement(['Dhaka', 'Chattogram', 'Sylhet', 'Khulna', 'Rajshahi']),
            'brand' => $this->faker->randomElement(['PetNest', 'PawCare', 'FurLine', 'VetCare']),
            'pet_type' => self::PET_TYPES[$slug],
            'age_group' => $this->faker->randomElement(['Puppy/Kitten', 'Junior', 'Adult', 'Senior', 'All Ages']),
            'tags' => ['pet-product', 'marketplace', $slug],
            'sku' => strtoupper(Str::random(8)) . '-' . $this->faker->unique()->numberBetween(10000, 99999),
            'rating' => $this->faker->randomFloat(2, 3.5, 5.0),
            'review_count' => $this->faker->numberBetween(0, 2000),
            'is_available' => $stock > 0,
        ];
    }
}
