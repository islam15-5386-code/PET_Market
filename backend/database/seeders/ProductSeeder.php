<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $categories = Category::pluck('id', 'slug');

        $products = [
            // ── Dog Food ──────────────────────────────────────────────────────
            [
                'category' => 'dog-food',
                'name'     => 'Royal Canin Medium Adult 4kg',
                'desc'     => 'Complete dry food for medium breed adult dogs (11–25 kg). Supports healthy skin and digestive health with precise fibre content.',
                'price'    => 1850.00,
                'stock'    => 30,
                'location' => 'Dhaka',
            ],
            [
                'category' => 'dog-food',
                'name'     => 'Pedigree Adult Chicken & Vegetables 3kg',
                'desc'     => 'Wholesome dry dog food with chicken, vegetables, and added vitamins and minerals. Perfect for daily feeding.',
                'price'    => 650.00,
                'stock'    => 50,
                'location' => 'Chittagong',
            ],
            [
                'category' => 'dog-food',
                'name'     => 'Orijen Original Grain-Free 2kg',
                'desc'     => 'Biologically appropriate dog food featuring 85% quality animal ingredients. Free from grains, potato and tapioca.',
                'price'    => 3200.00,
                'stock'    => 15,
                'location' => 'Dhaka',
            ],
            [
                'category' => 'dog-food',
                'name'     => 'Acana Heritage Free-Run Poultry 1.8kg',
                'desc'     => 'Made with 60% free-run chicken, turkey and nest-laid eggs. Biologically appropriate with whole prey ratios.',
                'price'    => 2750.00,
                'stock'    => 17,
                'location' => 'Dhaka',
            ],
            // ── Cat Food ──────────────────────────────────────────────────────
            [
                'category' => 'cat-food',
                'name'     => 'Whiskas Adult Tuna 1.2kg',
                'desc'     => 'Complete balanced nutrition for adult cats with real tuna. Contains Omega 6 fatty acids for a healthy coat.',
                'price'    => 480.00,
                'stock'    => 60,
                'location' => 'Sylhet',
            ],
            [
                'category' => 'cat-food',
                'name'     => 'Royal Canin Indoor Cat 2kg',
                'desc'     => 'Specially formulated for adult indoor cats. Helps manage healthy weight and reduce hairball formation.',
                'price'    => 1650.00,
                'stock'    => 25,
                'location' => 'Dhaka',
            ],
            // ── Pet Grooming ──────────────────────────────────────────────────
            [
                'category' => 'pet-grooming',
                'name'     => 'Wahl Professional Pet Shampoo 500ml',
                'desc'     => 'Mild tearless formula shampoo for dogs and cats. pH balanced with coconut oil for a shiny, healthy coat.',
                'price'    => 450.00,
                'stock'    => 40,
                'location' => 'Dhaka',
            ],
            [
                'category' => 'pet-grooming',
                'name'     => 'Hertzko Self Cleaning Slicker Brush',
                'desc'     => 'Fine bent wires remove loose fur, tangles and dander. Press button to retract bristles for easy clean-up.',
                'price'    => 890.00,
                'stock'    => 20,
                'location' => 'Rajshahi',
            ],
            // ── Pet Toys ──────────────────────────────────────────────────────
            [
                'category' => 'pet-toys',
                'name'     => 'Kong Classic Rubber Dog Toy',
                'desc'     => 'Fill with treats or peanut butter to keep your dog mentally stimulated for hours. Made from natural rubber.',
                'price'    => 750.00,
                'stock'    => 35,
                'location' => 'Dhaka',
            ],
            [
                'category' => 'pet-toys',
                'name'     => 'Interactive Feather Wand Cat Toy',
                'desc'     => 'Teaser wand with feathers and bells to stimulate your cat\'s natural hunting instincts.',
                'price'    => 220.00,
                'stock'    => 55,
                'location' => 'Chittagong',
            ],
            // ── Pet Health ────────────────────────────────────────────────────
            [
                'category' => 'pet-health',
                'name'     => 'Frontline Plus Flea & Tick Treatment 3-Pack',
                'desc'     => 'Fast-acting flea and tick treatment. Kills fleas, ticks and chewing lice. Waterproof. Lasts 30 days.',
                'price'    => 1200.00,
                'stock'    => 18,
                'location' => 'Dhaka',
            ],
            [
                'category' => 'pet-health',
                'name'     => 'Beaphar VitaMin Supplement 150g',
                'desc'     => 'All-round vitamin and mineral supplement for dogs and cats. Supports growth, immune function and energy.',
                'price'    => 580.00,
                'stock'    => 28,
                'location' => 'Sylhet',
            ],
            // ── Collars & Leads ───────────────────────────────────────────────
            [
                'category' => 'collars-leads',
                'name'     => 'Ruffwear Front Range Harness',
                'desc'     => 'Lightweight everyday harness with two leash attachment points. Padded chest and belly panel for all-day comfort.',
                'price'    => 2100.00,
                'stock'    => 12,
                'location' => 'Dhaka',
            ],
            [
                'category' => 'collars-leads',
                'name'     => 'Genuine Leather Dog Collar with Quick Release',
                'desc'     => 'Real leather collar with stainless steel hardware. Adjustable sizing fits necks 30–45 cm.',
                'price'    => 380.00,
                'stock'    => 45,
                'location' => 'Chittagong',
            ],
            // ── Pet Beds ──────────────────────────────────────────────────────
            [
                'category' => 'pet-beds',
                'name'     => 'Orthopedic Memory Foam Dog Bed Large',
                'desc'     => 'High-density memory foam provides joint support for dogs of all ages. Removable machine-washable cover.',
                'price'    => 3500.00,
                'stock'    => 8,
                'location' => 'Dhaka',
            ],
            // ── Fish & Aquatics ───────────────────────────────────────────────
            [
                'category' => 'fish-aquatics',
                'name'     => 'Fluval Spec V 19L Aquarium Kit',
                'desc'     => 'Complete kit with 3-stage filtration and energy-efficient LED lighting. Perfect for desktop placement.',
                'price'    => 4800.00,
                'stock'    => 6,
                'location' => 'Dhaka',
            ],
            [
                'category' => 'fish-aquatics',
                'name'     => 'Tetra ColorFin Fish Food 100g',
                'desc'     => 'Enhances natural fish colors with carotenoids. Suitable for all ornamental fish. Produces less waste.',
                'price'    => 280.00,
                'stock'    => 70,
                'location' => 'Rajshahi',
            ],
            // ── Bird Supplies ─────────────────────────────────────────────────
            [
                'category' => 'bird-supplies',
                'name'     => 'Versele-Laga Prestige Parakeet Seed Mix 1kg',
                'desc'     => 'Premium seed mix for budgerigars and parakeets. Contains 12 different grains for a varied, balanced diet.',
                'price'    => 320.00,
                'stock'    => 40,
                'location' => 'Sylhet',
            ],
            // ── Small Animals ─────────────────────────────────────────────────
            [
                'category' => 'small-animals',
                'name'     => 'Hamster Habitat Cage with Full Accessories',
                'desc'     => 'Large wire cage with deep plastic base for burrowing. Includes wheel, water bottle, food bowl and hideout.',
                'price'    => 1450.00,
                'stock'    => 10,
                'location' => 'Dhaka',
            ],
            [
                'category' => 'small-animals',
                'name'     => 'Supreme Selective Rabbit Food 1.5kg',
                'desc'     => 'High-fibre nugget food for adult rabbits. Supports healthy gut function and dental wear. No added sugar.',
                'price'    => 690.00,
                'stock'    => 22,
                'location' => 'Chittagong',
            ],
        ];

        $created = 0;

        foreach ($products as $p) {
            $categoryId = $categories[$p['category']] ?? null;

            if (!$categoryId) {
                $this->command->warn("⚠️  Category '{$p['category']}' not found — skipping.");
                continue;
            }

            Product::firstOrCreate(
                ['slug' => Str::slug($p['name'])],
                [
                    'category_id'    => $categoryId,
                    'name'           => $p['name'],
                    'description'    => $p['desc'],
                    'price'          => $p['price'],
                    'stock_quantity' => $p['stock'],
                    'location'       => $p['location'],
                    'is_available'   => true,
                    'image_url'      => $this->seedImageForCategory($p['category']),
                    'images'         => [$this->seedImageForCategory($p['category'])],
                    'pet_type'       => $this->petTypeForCategory($p['category']),
                    'age_group'      => 'All Ages',
                    'tags'           => [$p['category'], 'seeded'],
                ]
            );

            $created++;
        }

        $this->command->info("✅ {$created} products seeded.");
    }

    private function seedImageForCategory(string $categorySlug): string
    {
        return match ($categorySlug) {
            'dog-food' => 'https://images.unsplash.com/photo-1583512603805-3cc6b41f3edb?auto=format&fit=crop&w=1200&q=80',
            'cat-food' => 'https://images.unsplash.com/photo-1548767797-d8c844163c4c?auto=format&fit=crop&w=1200&q=80',
            'bird-supplies' => 'https://images.unsplash.com/photo-1444464666168-49d633b86797?auto=format&fit=crop&w=1200&q=80',
            'fish-aquatics' => 'https://images.unsplash.com/photo-1474511320723-9a56873867b5?auto=format&fit=crop&w=1200&q=80',
            'pet-grooming' => 'https://images.unsplash.com/photo-1516734212186-a967f81ad0d7?auto=format&fit=crop&w=1200&q=80',
            'pet-health' => 'https://images.unsplash.com/photo-1628009368231-7bb7cfcb0def?auto=format&fit=crop&w=1200&q=80',
            'pet-toys' => 'https://images.unsplash.com/photo-1568640347023-a616a30bc3bd?auto=format&fit=crop&w=1200&q=80',
            'collars-leads' => 'https://images.unsplash.com/photo-1450778869180-41d0601e046e?auto=format&fit=crop&w=1200&q=80',
            'pet-beds' => 'https://images.unsplash.com/photo-1548199973-03cce0bbc87b?auto=format&fit=crop&w=1200&q=80',
            default => 'https://images.unsplash.com/photo-1585110396000-c9ffd4e4b308?auto=format&fit=crop&w=1200&q=80',
        };
    }

    private function petTypeForCategory(string $categorySlug): string
    {
        return match ($categorySlug) {
            'dog-food', 'collars-leads' => 'Dog',
            'cat-food' => 'Cat',
            'bird-supplies' => 'Bird',
            'fish-aquatics' => 'Fish',
            'small-animals' => 'Small Animal',
            default => 'Mixed',
        };
    }
}
