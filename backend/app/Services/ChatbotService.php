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

        $localContext = $this->aiClient->localContext($message);
        $candidateProducts = collect();
        if ($localContext['wants_product_recommendations'] ?? false) {
            $candidateProducts = $this->recommendationService->recommend(
                $localContext['recommended_product_filters'] ?? [],
                allowGenericFallback: true,
            );
        }

        try {
            $ai = $this->aiClient->ask([
                'message' => $message,
                'session_id' => $session->session_uuid,
                'user_id' => $userId,
                'conversation_history' => $history,
                'local_context' => $localContext,
                'product_context' => $candidateProducts->map(fn ($p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'price' => (float) $p->price,
                    'stock' => (int) $p->stock_quantity,
                    'brand' => $p->brand,
                    'pet_type' => $p->pet_type,
                    'age_group' => $p->age_group,
                    'category' => $p->category?->name,
                    'description' => $p->description,
                ])->values()->all(),
            ]);
        } catch (\Throwable) {
            $ai = $this->aiClient->fallbackResponse($message, $localContext, apiUnavailable: true);
        }

        $wantsProducts = (bool) ($ai['wants_product_recommendations'] ?? $localContext['wants_product_recommendations'] ?? false);
        $filters = array_filter(array_merge(
            $localContext['recommended_product_filters'] ?? [],
            $ai['recommended_product_filters'] ?? [],
        ), fn ($value) => $value !== null && $value !== '');

        $products = $wantsProducts
            ? $this->recommendationService->recommend($filters, allowGenericFallback: true)
            : collect();

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
                'image' => $this->resolveProductImage($p),
                'rating' => (float) $p->rating,
                'slug' => $p->slug,
                'brand' => $p->brand,
                'pet_type' => $p->pet_type,
                'age_group' => $p->age_group,
                'category' => $p->category?->name,
                'stock' => (int) $p->stock_quantity,
                'description' => $p->description,
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

    private function resolveProductImage($product): ?string
    {
        if (!empty($product->image_url)) {
            return str_starts_with($product->image_url, 'http') || str_starts_with($product->image_url, '/')
                ? $product->image_url
                : asset('storage/' . $product->image_url);
        }

        $images = $product->images ?? [];
        $first = $images[0] ?? null;
        if (!$first) {
            return null;
        }

        return str_starts_with($first, 'http') || str_starts_with($first, '/')
            ? $first
            : asset('storage/' . $first);
    }
}
