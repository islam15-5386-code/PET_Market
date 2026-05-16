<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class AIDescriptionGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthorized_user_cannot_generate_description(): void
    {
        $category = Category::query()->create(['name' => 'Cat Food', 'slug' => 'cat-food']);
        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Test Cat Food',
            'slug' => 'test-cat-food',
            'price' => 500,
            'stock_quantity' => 10,
            'is_available' => true,
        ]);

        $this->postJson("/api/admin/products/{$product->id}/generate-description")
            ->assertStatus(401);
    }

    public function test_admin_can_generate_and_reuse_cached_description(): void
    {
        Http::fake([
            'http://127.0.0.1:8001/ai/product-description/generate' => Http::response([
                'source' => 'ai_model',
                'title' => 'Premium Kitten Dry Food by Meow Mix',
                'description' => 'Balanced kitten nutrition with protein-rich formula for healthy daily growth and easy digestion.',
                'seo_keywords' => ['kitten dry food', 'cat food bangladesh', 'meow mix kitten', 'high protein cat food', 'kitten nutrition'],
                'benefits' => ['Supports healthy growth', 'Easy to digest', 'Protein-rich daily nutrition'],
            ], 200),
        ]);

        $category = Category::query()->create(['name' => 'Cat Food', 'slug' => 'cat-food']);
        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Kitten Dry Food',
            'slug' => 'kitten-dry-food',
            'description' => 'high protein, easy digestion',
            'price' => 750,
            'stock_quantity' => 25,
            'is_available' => true,
            'brand' => 'Meow Mix',
            'location' => 'Bangladesh',
        ]);

        $admin = User::query()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('Password@123'),
            'role' => 'admin',
            'is_active' => true,
        ]);
        $token = JWTAuth::fromUser($admin);

        $first = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/admin/products/{$product->id}/generate-description")
            ->assertOk()
            ->json();

        $this->assertTrue($first['success']);
        $this->assertFalse($first['data']['cached']);

        $second = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/admin/products/{$product->id}/generate-description")
            ->assertOk()
            ->json();

        $this->assertTrue($second['success']);
        $this->assertTrue($second['data']['cached']);
        Http::assertSentCount(1);
    }
}
