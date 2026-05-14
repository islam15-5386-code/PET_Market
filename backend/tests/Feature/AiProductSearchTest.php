<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiProductSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_ai_search_returns_products_and_logs_query(): void
    {
        Http::fake([
            'http://127.0.0.1:8001/search/parse' => Http::response([
                'intent' => 'product_search',
                'pet_type' => 'cat',
                'age_group' => 'kitten',
                'category' => 'food',
                'brand' => null,
                'price_min' => null,
                'price_max' => 1000,
                'keywords' => ['kitten', 'food'],
                'confidence' => 0.92,
            ], 200),
        ]);

        $category = Category::query()->create([
            'name' => 'Cat Food',
            'slug' => 'cat-food',
            'description' => 'Cat food category',
        ]);

        Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Kitten Dry Food',
            'slug' => 'kitten-dry-food',
            'description' => 'Great kitten food',
            'price' => 900,
            'stock_quantity' => 25,
            'is_available' => true,
            'brand' => 'Whiskas',
        ]);

        $response = $this->postJson('/api/ai-search', [
            'query' => 'I need good food for my kitten under 1000 BDT',
        ])->assertOk();

        $response->assertJsonPath('success', true);
        $response->assertJsonCount(1, 'data.products');
        $response->assertJsonPath('data.ai_filters.pet_type', 'cat');

        $this->assertDatabaseCount('ai_search_logs', 1);
        $this->assertDatabaseHas('ai_search_logs', [
            'query' => 'I need good food for my kitten under 1000 BDT',
            'detected_pet_type' => 'cat',
            'detected_category' => 'food',
            'total_results' => 1,
        ]);
    }
}
