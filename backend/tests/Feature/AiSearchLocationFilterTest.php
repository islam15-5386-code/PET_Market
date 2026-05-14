<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiSearchLocationFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_dhaka_query_returns_only_dhaka_exact_results(): void
    {
        $cat = Category::query()->create(['name' => 'Pet Grooming', 'slug' => 'pet-grooming', 'description' => 'Grooming']);
        Product::factory()->create([
            'category_id' => $cat->id,
            'name' => 'Puppy Shampoo Dhaka',
            'location' => 'Dhaka',
            'pet_type' => 'Dog',
            'age_group' => 'Puppy',
            'price' => 400,
            'is_available' => true,
            'stock_quantity' => 12,
        ]);
        Product::factory()->create([
            'category_id' => $cat->id,
            'name' => 'Puppy Shampoo Rajshahi',
            'location' => 'Rajshahi',
            'pet_type' => 'Dog',
            'age_group' => 'Puppy',
            'price' => 400,
            'is_available' => true,
            'stock_quantity' => 12,
        ]);

        Http::fake([
            '*/ai/route' => Http::response([
                'feature' => 'product_search',
                'strategy_used' => 'rule_based',
                'result' => [
                    'category' => 'pet-grooming',
                    'pet_type' => 'dog',
                    'age_group' => 'puppy',
                    'location' => 'Dhaka',
                    'price_min' => null,
                    'price_max' => null,
                    'keywords' => ['puppy', 'shampoo'],
                ],
                'token_usage' => ['prompt_tokens' => 0, 'completion_tokens' => 0, 'total_tokens' => 0],
            ], 200),
        ]);

        $res = $this->postJson('/api/ai-search', ['query' => 'puppy shampoo in Dhaka']);

        $res->assertOk();
        $res->assertJsonPath('data.result_mode', 'exact');
        $this->assertCount(1, $res->json('data.exact_results'));
        $this->assertSame('Dhaka', $res->json('data.exact_results.0.location'));
    }

    public function test_chattogram_price_filter_applies_on_exact_results(): void
    {
        $cat = Category::query()->create(['name' => 'Cat Food', 'slug' => 'cat-food', 'description' => 'Food']);
        Product::factory()->create([
            'category_id' => $cat->id,
            'name' => 'Cat Food Chattogram 1200',
            'location' => 'Chattogram',
            'pet_type' => 'Cat',
            'price' => 1200,
            'is_available' => true,
            'stock_quantity' => 10,
        ]);
        Product::factory()->create([
            'category_id' => $cat->id,
            'name' => 'Cat Food Chattogram 1800',
            'location' => 'Chattogram',
            'pet_type' => 'Cat',
            'price' => 1800,
            'is_available' => true,
            'stock_quantity' => 10,
        ]);

        Http::fake([
            '*/ai/route' => Http::response([
                'feature' => 'product_search',
                'strategy_used' => 'rule_based',
                'result' => [
                    'category' => 'cat-food',
                    'pet_type' => 'cat',
                    'location' => 'Chattogram',
                    'price_max' => 1500,
                    'keywords' => ['cat', 'food'],
                ],
                'token_usage' => ['prompt_tokens' => 0, 'completion_tokens' => 0, 'total_tokens' => 0],
            ], 200),
        ]);

        $res = $this->postJson('/api/ai-search', ['query' => 'cat food in Chattogram under 1500']);

        $res->assertOk();
        $this->assertCount(1, $res->json('data.exact_results'));
        $this->assertLessThanOrEqual(1500, (float) $res->json('data.exact_results.0.price'));
    }

    public function test_no_exact_location_returns_fallback_with_message(): void
    {
        $cat = Category::query()->create(['name' => 'Dog Food', 'slug' => 'dog-food', 'description' => 'Food']);
        Product::factory()->create([
            'category_id' => $cat->id,
            'name' => 'Dog Food Rajshahi',
            'location' => 'Rajshahi',
            'pet_type' => 'Dog',
            'price' => 900,
            'is_available' => true,
            'stock_quantity' => 8,
        ]);

        Http::fake([
            '*/ai/route' => Http::response([
                'feature' => 'product_search',
                'strategy_used' => 'rule_based',
                'result' => [
                    'category' => 'dog-food',
                    'pet_type' => 'dog',
                    'location' => 'Dhaka',
                    'keywords' => ['dog', 'food'],
                ],
                'token_usage' => ['prompt_tokens' => 0, 'completion_tokens' => 0, 'total_tokens' => 0],
            ], 200),
        ]);

        $res = $this->postJson('/api/ai-search', ['query' => 'dog food in Dhaka']);

        $res->assertOk();
        $res->assertJsonPath('data.result_mode', 'fallback');
        $this->assertCount(0, $res->json('data.exact_results'));
        $this->assertGreaterThan(0, count($res->json('data.fallback_results')));
        $this->assertStringContainsString('No exact products found in Dhaka', $res->json('data.message'));
    }
}
