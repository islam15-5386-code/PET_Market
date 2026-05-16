<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductListingApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_endpoint_returns_ok(): void
    {
        $this->getJson('/api/health')
            ->assertOk()
            ->assertJsonPath('status', 'ok');
    }

    public function test_products_endpoint_supports_pagination_and_filters(): void
    {
        $category = Category::query()->create(['name' => 'Dog Food', 'slug' => 'dog-food']);

        Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Dhaka Puppy Meal',
            'slug' => 'dhaka-puppy-meal',
            'price' => 450,
            'stock_quantity' => 8,
            'is_available' => true,
            'is_active' => true,
            'location' => 'Dhaka',
            'pet_type' => 'dog',
            'age_group' => 'puppy',
            'image_url' => 'https://example.com/dhaka.jpg',
            'rating' => 4.8,
        ]);

        Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Rajshahi Puppy Meal',
            'slug' => 'rajshahi-puppy-meal',
            'price' => 550,
            'stock_quantity' => 8,
            'is_available' => true,
            'is_active' => true,
            'location' => 'Rajshahi',
            'pet_type' => 'dog',
            'age_group' => 'puppy',
            'images' => ['https://example.com/rajshahi.jpg'],
            'rating' => 4.1,
        ]);

        $response = $this->getJson('/api/products?per_page=1&category=dog-food&location=Dhaka&min_price=300&max_price=5000&sort=rating');

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.meta.per_page', 1);
        $response->assertJsonCount(1, 'data.products');
        $response->assertJsonPath('data.products.0.location', 'Dhaka');
        $response->assertJsonPath('data.products.0.image_url', 'https://example.com/dhaka.jpg');
    }
}
