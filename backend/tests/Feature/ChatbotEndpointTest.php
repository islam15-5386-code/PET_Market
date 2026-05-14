<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ChatbotEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_chat_creates_session_and_messages_and_recommendations(): void
    {
        Http::fake([
            'http://127.0.0.1:8001/chatbot/message' => Http::response([
                'reply' => 'For a puppy, choose balanced puppy food.',
                'intent' => 'food_advice',
                'pet_type' => 'dog',
                'category' => 'food',
                'age_group' => 'puppy',
                'price_min' => null,
                'price_max' => 1000,
                'safety_level' => 'safe',
                'vet_warning' => null,
                'recommended_product_filters' => [
                    'pet_type' => 'dog',
                    'category' => 'food',
                    'age_group' => 'puppy',
                    'price_max' => 1000,
                ],
                'confidence' => 0.91,
            ], 200),
        ]);

        $category = Category::query()->create(['name' => 'Dog Food', 'slug' => 'dog-food']);
        Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Puppy Food Basic',
            'slug' => 'puppy-food-basic',
            'description' => 'good for puppy',
            'price' => 950,
            'stock_quantity' => 10,
            'is_available' => true,
            'rating' => 4.8,
            'images' => [],
        ]);

        $response = $this->postJson('/api/chatbot/message', [
            'message' => 'Which food is good for my puppy?',
            'session_id' => 'guest-1',
        ])->assertOk()->json();

        $this->assertTrue($response['success']);
        $this->assertSame('food_advice', $response['data']['intent']);
        $this->assertCount(1, $response['data']['recommended_products']);

        $this->assertDatabaseCount('chatbot_sessions', 1);
        $this->assertDatabaseCount('chatbot_messages', 2);
        $this->assertDatabaseCount('chatbot_recommendations', 1);
    }

    public function test_chatbot_fallback_when_ai_down(): void
    {
        Http::fake([
            '*' => Http::response([], 500),
        ]);

        $response = $this->postJson('/api/chatbot/message', [
            'message' => 'My cat has bleeding',
            'session_id' => 'guest-2',
        ])->assertOk()->json();

        $this->assertTrue($response['success']);
        $this->assertSame('emergency_warning', $response['data']['intent']);
        $this->assertStringContainsString('veterinarian immediately', strtolower($response['data']['reply']));
    }
}
