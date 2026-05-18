<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ChatbotRecommendationService
{
    public function recommend(array $filters, bool $allowGenericFallback = true): Collection
    {
        $op = DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';

        $q = Product::query()->with('category')->available();

        $category = $filters['category'] ?? null;
        $petType = $filters['pet_type'] ?? null;
        $ageGroup = $filters['age_group'] ?? null;
        $priceMin = $filters['price_min'] ?? null;
        $priceMax = $filters['price_max'] ?? null;

        if (
            !$allowGenericFallback &&
            !$category &&
            !$petType &&
            !$ageGroup &&
            is_null($priceMin) &&
            is_null($priceMax)
        ) {
            return collect();
        }

        if ($category) {
            $q->whereHas('category', fn ($c) => $c->where('name', $op, "%{$category}%")->orWhere('slug', $op, "%{$category}%"));
        }

        if ($petType) {
            $q->where(function ($sub) use ($petType, $op) {
                $sub->where('name', $op, "%{$petType}%")
                    ->orWhere('description', $op, "%{$petType}%")
                    ->orWhere('pet_type', $op, "%{$petType}%");
            });
        }

        if ($ageGroup) {
            $q->where(function ($sub) use ($ageGroup, $op) {
                $sub->where('name', $op, "%{$ageGroup}%")
                    ->orWhere('description', $op, "%{$ageGroup}%")
                    ->orWhere('age_group', $op, "%{$ageGroup}%");
            });
        }

        if (!is_null($priceMin)) {
            $q->where('price', '>=', (float) $priceMin);
        }
        if (!is_null($priceMax)) {
            $q->where('price', '<=', (float) $priceMax);
        }

        $exact = (clone $q)->orderByDesc('rating')->orderByDesc('stock_quantity')->limit(5)->get();
        if ($exact->isNotEmpty()) {
            return $exact;
        }

        if ($category) {
            $fallbackCategory = Product::query()->with('category')->available()
                ->whereHas('category', fn ($c) => $c->where('name', $op, "%{$category}%")->orWhere('slug', $op, "%{$category}%"))
                ->orderByDesc('rating')->orderByDesc('stock_quantity')->limit(5)->get();
            if ($fallbackCategory->isNotEmpty()) {
                return $fallbackCategory;
            }
        }

        if ($petType) {
            $fallbackPet = Product::query()->with('category')->available()
                ->where(function ($sub) use ($petType, $op) {
                    $sub->where('name', $op, "%{$petType}%")
                        ->orWhere('description', $op, "%{$petType}%")
                        ->orWhere('pet_type', $op, "%{$petType}%");
                })
                ->orderByDesc('rating')->orderByDesc('stock_quantity')->limit(5)->get();
            if ($fallbackPet->isNotEmpty()) {
                return $fallbackPet;
            }
        }

        if (!$allowGenericFallback) {
            return collect();
        }

        return Product::query()->with('category')->available()->orderByDesc('rating')->orderByDesc('stock_quantity')->limit(5)->get();
    }
}
