<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AISearchStrictLocationTest extends TestCase
{
    use RefreshDatabase;

    public function test_ai_search_returns_fallback_results_when_exact_location_has_no_match(): void
    {
        config()->set('services.ai_service.semantic_search_enabled', false);

        Http::fake([
            'http://127.0.0.1:8001/ai/product-search' => Http::response([
                'category' => 'grooming',
                'pet_type' => 'dog',
                'age_group' => 'puppy',
                'location' => 'Dhaka',
                'keywords' => ['shampoo', 'puppy'],
                'price_min' => null,
                'price_max' => null,
            ], 200),
        ]);

        $category = Category::query()->create(['name' => 'Grooming', 'slug' => 'grooming']);

        Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Puppy Shampoo Rajshahi',
            'slug' => 'puppy-shampoo-rajshahi',
            'price' => 420,
            'stock_quantity' => 10,
            'is_available' => true,
            'is_active' => true,
            'location' => 'Rajshahi',
            'pet_type' => 'dog',
            'age_group' => 'puppy',
            'images' => ['https://example.com/a.jpg'],
        ]);

        $response = $this->postJson('/api/ai-search', ['query' => 'puppy shampoo in Dhaka']);
        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.result_mode', 'fallback');
        $response->assertJsonCount(0, 'data.exact_results');
        $response->assertJsonCount(1, 'data.fallback_results');
        $response->assertJsonPath('data.fallback_results.0.location', 'Rajshahi');
    }
}

