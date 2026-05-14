<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class AiProductDescriptionGeneratorTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $override = []): array
    {
        return array_merge([
            'product_name' => 'Cat Food',
            'category' => 'Food',
            'pet_type' => 'Cat',
            'age_group' => 'Kitten',
            'brand' => 'MeowCare',
            'price' => 750,
            'weight_or_size' => '1kg',
            'ingredients_or_materials' => 'Chicken, fish oil',
            'key_features' => 'High protein, easy digestion',
            'usage_instruction' => 'Serve by kitten weight',
            'safety_note' => 'Keep in dry place',
            'target_customer' => 'Kitten owners',
            'language' => 'English',
            'tone' => 'SEO optimized',
        ], $override);
    }

    public function test_unauthorized_user_cannot_generate(): void
    {
        $this->postJson('/api/ai/product-description/generate', $this->payload())
            ->assertStatus(401);
    }

    public function test_admin_can_generate_and_log(): void
    {
        Http::fake([
            'http://127.0.0.1:8001/ai/product-description/generate' => Http::response([
                'professional_product_title' => 'MeowCare Nutritious Kitten Cat Food - 1kg',
                'short_description' => 'Balanced nutrition for growing kittens.',
                'long_description' => 'Long description content.',
                'seo_keywords' => ['kitten food', 'cat food bangladesh', 'meowcare cat food', 'pet food', 'nutritious kitten meal'],
                'benefits' => ['Supports healthy growth', 'Easy to digest', 'Rich nutrients'],
                'care_instruction' => 'Store in cool dry place.',
                'usage_instruction' => 'Serve by weight.',
                'safety_warning' => 'Consult a veterinarian for medical concerns.',
                'meta_title' => 'MeowCare Kitten Food',
                'meta_description' => 'Nutritious kitten food in Bangladesh.',
                'suggested_tags' => ['cat', 'kitten', 'food', 'pet care'],
                'provider_name' => 'openai',
                'model_name' => 'gpt-4o-mini',
                'token_usage' => [
                    'prompt_tokens' => 120,
                    'completion_tokens' => 210,
                    'total_tokens' => 330,
                ],
            ], 200),
        ]);

        $admin = User::query()->create([
            'name' => 'Admin',
            'email' => 'adminx@example.com',
            'password' => bcrypt('Password@123'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $token = JWTAuth::fromUser($admin);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/ai/product-description/generate', $this->payload())
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.provider_name', 'openai');

        $this->assertDatabaseHas('ai_product_description_logs', [
            'user_id' => $admin->id,
            'status' => 'success',
            'provider_name' => 'openai',
        ]);
    }

    public function test_fallback_works_when_ai_service_fails(): void
    {
        Http::fake([
            '*' => Http::response([], 500),
        ]);

        $seller = User::query()->create([
            'name' => 'Seller',
            'email' => 'seller@example.com',
            'password' => bcrypt('Password@123'),
            'role' => 'user',
            'is_active' => true,
        ]);

        $token = JWTAuth::fromUser($seller);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/ai/product-description/generate', $this->payload())
            ->assertOk()
            ->assertJsonPath('data.provider_name', 'fallback');

        $this->assertDatabaseHas('ai_product_description_logs', [
            'status' => 'fallback',
            'provider_name' => 'fallback',
        ]);
    }

    public function test_generated_content_can_be_saved_into_product(): void
    {
        Http::fake([
            'http://127.0.0.1:8001/ai/product-description/generate' => Http::response([
                'professional_product_title' => 'AI Product Title',
                'short_description' => 'AI short.',
                'long_description' => 'AI long.',
                'seo_keywords' => ['k1', 'k2', 'k3', 'k4', 'k5'],
                'benefits' => ['b1', 'b2', 'b3'],
                'care_instruction' => 'care',
                'usage_instruction' => 'usage',
                'safety_warning' => 'Consult a veterinarian for medical concerns.',
                'meta_title' => 'meta title',
                'meta_description' => 'meta desc',
                'suggested_tags' => ['cat', 'food'],
                'provider_name' => 'openai',
                'model_name' => 'gpt-4o-mini',
                'token_usage' => ['prompt_tokens' => 1, 'completion_tokens' => 2, 'total_tokens' => 3],
            ], 200),
        ]);

        $category = Category::query()->create(['name' => 'Cat Food', 'slug' => 'cat-food']);
        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Base Product',
            'slug' => 'base-product',
            'price' => 100,
            'stock_quantity' => 4,
            'is_available' => true,
        ]);

        $admin = User::query()->create([
            'name' => 'Admin',
            'email' => 'admin2@example.com',
            'password' => bcrypt('Password@123'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $token = JWTAuth::fromUser($admin);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/ai/product-description/generate', $this->payload(['product_id' => $product->id]))
            ->assertOk();

        $product->refresh();
        $this->assertSame('AI Product Title', $product->ai_generated_title);
        $this->assertSame('AI short.', $product->ai_generated_short_description);
    }
}
