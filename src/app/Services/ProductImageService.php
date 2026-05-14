<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Str;

class ProductImageService
{
    private const GLOBAL_FALLBACK = '/products/fallback/pet-product-placeholder.jpg';

    public function normalizePetType(?string $petType): string
    {
        $value = Str::of((string) $petType)->lower()->replace(['&', '/', '-'], ' ')->trim()->value();
        return match (true) {
            str_contains($value, 'cat'), str_contains($value, 'kitten') => 'cat',
            str_contains($value, 'dog'), str_contains($value, 'puppy') => 'dog',
            str_contains($value, 'bird'), str_contains($value, 'parrot'), str_contains($value, 'canary') => 'bird',
            str_contains($value, 'fish'), str_contains($value, 'aquatic'), str_contains($value, 'aquarium') => 'fish',
            str_contains($value, 'rabbit'), str_contains($value, 'bunny') => 'rabbit',
            str_contains($value, 'hamster'), str_contains($value, 'guinea pig') => 'hamster',
            default => '',
        };
    }

    public function normalizeCategory(?string $category): string
    {
        $value = Str::of((string) $category)->lower()->replace(['&', '/', '-'], ' ')->trim()->value();
        return match (true) {
            str_contains($value, 'food'), str_contains($value, 'nutrition') => 'food',
            str_contains($value, 'health'), str_contains($value, 'medicine'), str_contains($value, 'vitamin') => 'medicine',
            str_contains($value, 'groom') => 'grooming',
            str_contains($value, 'toy') => 'toys',
            str_contains($value, 'collar'), str_contains($value, 'lead'), str_contains($value, 'bed'),
            str_contains($value, 'cage'), str_contains($value, 'accessor'), str_contains($value, 'aquatic') => 'accessories',
            default => '',
        };
    }

    public function normalizeSubCategory(?string $subCategory): string
    {
        $value = Str::of((string) $subCategory)->lower()->replace(['&', '/', '-'], ' ')->trim()->value();
        return match (true) {
            str_contains($value, 'dry') => 'dry_food',
            str_contains($value, 'wet') => 'wet_food',
            str_contains($value, 'kitten') => 'kitten_food',
            str_contains($value, 'puppy') => 'puppy_food',
            str_contains($value, 'senior') => 'senior_food',
            str_contains($value, 'pellet') => 'pellet_food',
            str_contains($value, 'hay') => 'hay',
            str_contains($value, 'brush'), str_contains($value, 'comb') => 'brush',
            str_contains($value, 'shampoo') => 'shampoo',
            str_contains($value, 'nail') => 'nail_clipper',
            str_contains($value, 'vitamin') => 'vitamin',
            str_contains($value, 'flea'), str_contains($value, 'tick') => 'flea_treatment',
            str_contains($value, 'joint') => 'joint_care',
            str_contains($value, 'water conditioner') => 'water_conditioner',
            str_contains($value, 'chew') => 'chew_toy',
            str_contains($value, 'rope') => 'rope_toy',
            str_contains($value, 'squeaky') => 'squeaky_toy',
            str_contains($value, 'teaser') => 'teaser_toy',
            str_contains($value, 'interactive') => 'interactive_toy',
            str_contains($value, 'swing') => 'swing_toy',
            str_contains($value, 'tunnel') => 'tunnel_toy',
            str_contains($value, 'seed') => 'seed_mix',
            str_contains($value, 'flake') => 'flakes',
            str_contains($value, 'collar') => 'collar',
            str_contains($value, 'leash'), str_contains($value, 'lead') => 'leash',
            str_contains($value, 'harness') => 'harness',
            str_contains($value, 'bed') => 'bed',
            str_contains($value, 'cage') => 'cage',
            str_contains($value, 'bowl') => 'bowl',
            str_contains($value, 'perch') => 'perch',
            str_contains($value, 'filter') => 'filter',
            str_contains($value, 'air pump') => 'air_pump',
            str_contains($value, 'aquarium') => 'aquarium',
            str_contains($value, 'bedding') => 'bedding',
            default => '',
        };
    }

    public function inferPetTypeFromProduct(Product $product): string
    {
        $context = implode(' ', array_filter([
            $product->name,
            $product->description,
            $product->category?->slug,
            $product->category?->name,
        ]));
        return $this->normalizePetType($context);
    }

    public function inferCategoryFromProduct(Product $product): string
    {
        $context = implode(' ', array_filter([
            $product->category?->slug,
            $product->category?->name,
            $product->name,
        ]));
        return $this->normalizeCategory($context);
    }

    public function inferSubCategoryFromProduct(Product $product): string
    {
        $context = implode(' ', array_filter([$product->name, $product->description]));
        return $this->normalizeSubCategory($context);
    }

    public function getImage(?string $petType, ?string $category, ?string $subCategory): string
    {
        return $this->getMultipleImages($petType, $category, $subCategory, 1)[0] ?? self::GLOBAL_FALLBACK;
    }

    public function getMultipleImages(?string $petType, ?string $category, ?string $subCategory, int $count = 3): array
    {
        $pet = $this->normalizePetType($petType);
        $cat = $this->normalizeCategory($category);
        $sub = $this->normalizeSubCategory($subCategory);
        $map = config('product_images', []);

        $pool = [];

        // 1) exact pet + category + sub-category
        if ($pet && $cat && $sub) {
            $pool = $map[$pet][$cat][$sub] ?? [];
        }

        // 2) pet + category aggregate
        if (empty($pool) && $pet && $cat && isset($map[$pet][$cat]) && is_array($map[$pet][$cat])) {
            $pool = $this->flattenArray($map[$pet][$cat]);
        }

        // 3) category-level fallback
        if (empty($pool) && $cat) {
            $pool = $map['category_fallback'][$cat] ?? [];
        }

        // 4) global fallback
        if (empty($pool)) {
            $pool = $map['fallback'] ?? [self::GLOBAL_FALLBACK];
        }

        $unique = array_values(array_unique(array_filter($pool)));
        if (empty($unique)) {
            return [self::GLOBAL_FALLBACK];
        }

        if ($count <= 1) {
            return [$unique[0]];
        }

        $selected = [];
        for ($i = 0; $i < $count; $i++) {
            $selected[] = $unique[$i % count($unique)];
        }

        return $selected;
    }

    private function flattenArray(array $value): array
    {
        $flat = [];
        array_walk_recursive($value, static function ($item) use (&$flat) {
            if (is_string($item)) {
                $flat[] = $item;
            }
        });
        return $flat;
    }
}

