<?php

namespace App\Services;

use App\Models\ChatbotMessage;
use App\Models\ChatbotRecommendation;
use App\Models\ChatbotSession;
use Illuminate\Support\Str;

class ChatbotService
{
    public function __construct(
        private readonly AiChatbotClientService $aiClient,
        private readonly ChatbotRecommendationService $recommendationService,
    ) {
    }

    public function handle(string $message, ?string $sessionUuid, ?int $userId): array
    {
        $session = $this->resolveSession($sessionUuid, $userId);

        ChatbotMessage::query()->create([
            'chatbot_session_id' => $session->id,
            'sender' => 'user',
            'message' => $message,
        ]);

        $history = ChatbotMessage::query()
            ->where('chatbot_session_id', $session->id)
            ->latest('id')
            ->limit(8)
            ->get(['sender', 'message'])
            ->reverse()
            ->values()
            ->all();

        try {
            $ai = $this->aiClient->ask([
                'message' => $message,
                'session_id' => $session->session_uuid,
                'user_id' => $userId,
                'conversation_history' => $history,
            ]);
        } catch (\Throwable) {
            $ai = $this->fallbackAi($message);
        }

        $products = $this->recommendationService->recommend($ai['recommended_product_filters'] ?? []);

        $aiMessage = ChatbotMessage::query()->create([
            'chatbot_session_id' => $session->id,
            'sender' => 'ai',
            'message' => $ai['reply'] ?? 'Please try again shortly.',
            'intent' => $ai['intent'] ?? 'unknown',
            'pet_type' => $ai['pet_type'] ?? null,
            'category' => $ai['category'] ?? null,
            'age_group' => $ai['age_group'] ?? null,
            'safety_level' => $ai['safety_level'] ?? 'safe',
            'ai_payload' => $ai,
        ]);

        foreach ($products as $index => $product) {
            ChatbotRecommendation::query()->create([
                'chatbot_message_id' => $aiMessage->id,
                'product_id' => $product->id,
                'score' => max(0.1, 1 - ($index * 0.1)),
                'reason' => 'intent/category match',
            ]);
        }

        return [
            'session_id' => $session->session_uuid,
            'reply' => $ai['reply'] ?? '',
            'intent' => $ai['intent'] ?? 'unknown',
            'pet_type' => $ai['pet_type'] ?? null,
            'category' => $ai['category'] ?? null,
            'age_group' => $ai['age_group'] ?? null,
            'safety_level' => $ai['safety_level'] ?? 'safe',
            'vet_warning' => $ai['vet_warning'] ?? null,
            'recommended_products' => $products->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'price' => (float) $p->price,
                'image' => !empty($p->images) ? (str_starts_with($p->images[0], 'http') ? $p->images[0] : asset('storage/' . $p->images[0])) : null,
                'rating' => (float) $p->rating,
                'slug' => $p->slug,
            ])->values(),
        ];
    }

    private function resolveSession(?string $sessionUuid, ?int $userId): ChatbotSession
    {
        if ($sessionUuid) {
            $existing = ChatbotSession::query()->where('session_uuid', $sessionUuid)->first();
            if ($existing) {
                return $existing;
            }
        }

        return ChatbotSession::query()->create([
            'user_id' => $userId,
            'session_uuid' => $sessionUuid ?: (string) Str::uuid(),
            'status' => 'active',
            'started_at' => now(),
        ]);
    }

    private function fallbackAi(string $message): array
    {
        $lower = strtolower($message);
        $emergency = str_contains($lower, 'bleeding') || str_contains($lower, 'breathing') || str_contains($lower, 'seizure');
        if ($emergency) {
            return [
                'reply' => 'This may be serious. Please contact a veterinarian immediately.',
                'intent' => 'emergency_warning',
                'pet_type' => null,
                'category' => null,
                'age_group' => null,
                'safety_level' => 'emergency',
                'vet_warning' => 'This may be serious. Please contact a veterinarian immediately.',
                'recommended_product_filters' => [],
            ];
        }

        return [
            'reply' => 'I can help with food, grooming, and product suggestions. Please share pet type and need.',
            'intent' => 'unknown',
            'pet_type' => null,
            'category' => null,
            'age_group' => null,
            'safety_level' => 'safe',
            'vet_warning' => null,
            'recommended_product_filters' => [],
        ];
    }
}
