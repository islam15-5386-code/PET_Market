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
    ) {}

    public function processMessage(string $message, ?string $sessionId, ?int $userId): array
    {
        $session = $this->resolveSession($sessionId, $userId);

        ChatbotMessage::create([
            'chatbot_session_id' => $session->id,
            'sender' => 'user',
            'message' => $message,
        ]);

        $history = $session->messages()
            ->latest('id')
            ->limit(10)
            ->get(['sender', 'message'])
            ->reverse()
            ->values()
            ->map(fn ($m) => ['sender' => $m->sender, 'message' => $m->message])
            ->all();

        try {
            $ai = $this->aiClient->sendMessage([
                'message' => $message,
                'session_id' => $session->session_uuid,
                'user_id' => $userId,
                'conversation_history' => $history,
            ]);
        } catch (\Throwable $e) {
            $ai = $this->fallbackAiPayload($message);
        }

        $filters = $ai['recommended_product_filters'] ?? [];
        $products = $this->recommendationService->recommend($filters, 5);

        $aiMessage = ChatbotMessage::create([
            'chatbot_session_id' => $session->id,
            'sender' => 'ai',
            'message' => (string) ($ai['reply'] ?? 'Please try again.'),
            'intent' => $ai['intent'] ?? 'unknown',
            'pet_type' => $ai['pet_type'] ?? null,
            'category' => $ai['category'] ?? null,
            'age_group' => $ai['age_group'] ?? null,
            'safety_level' => $ai['safety_level'] ?? 'safe',
            'ai_payload' => $ai,
        ]);

        foreach ($products as $index => $product) {
            ChatbotRecommendation::create([
                'chatbot_message_id' => $aiMessage->id,
                'product_id' => $product->id,
                'score' => max(0.1, 1 - ($index * 0.1)),
                'reason' => 'Matched by AI filter and product availability',
            ]);
        }

        return [
            'session_id' => $session->session_uuid,
            'reply' => $ai['reply'] ?? 'Please try again.',
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
                'image' => $p->images[0] ?? null,
                'rating' => (float) ($p->rating ?? 0),
                'slug' => $p->slug,
            ])->values()->all(),
        ];
    }

    private function resolveSession(?string $sessionId, ?int $userId): ChatbotSession
    {
        if ($sessionId && Str::isUuid($sessionId)) {
            $session = ChatbotSession::query()->where('session_uuid', $sessionId)->first();
            if ($session) {
                if ($userId && !$session->user_id) {
                    $session->update(['user_id' => $userId]);
                }
                return $session;
            }
        }

        return ChatbotSession::create([
            'user_id' => $userId,
            'session_uuid' => (string) Str::uuid(),
            'status' => 'active',
            'started_at' => now(),
        ]);
    }

    private function fallbackAiPayload(string $message): array
    {
        $lower = Str::lower($message);
        $warningTerms = ['not eating', 'vomit', 'vomiting', 'bleeding', 'breathing', 'seizure', 'poison', 'fever', 'খাচ্ছে না'];

        $hasWarning = false;
        foreach ($warningTerms as $term) {
            if (str_contains($lower, $term)) {
                $hasWarning = true;
                break;
            }
        }

        return [
            'reply' => $hasWarning
                ? 'This may be serious. Please contact a veterinarian immediately. For non-emergency support, I can suggest suitable products too.'
                : 'I can help with food, grooming, and pet care suggestions. Please share your pet type and budget.',
            'intent' => $hasWarning ? 'health_warning' : 'general_pet_care',
            'pet_type' => null,
            'category' => null,
            'age_group' => null,
            'safety_level' => $hasWarning ? 'warning' : 'safe',
            'vet_warning' => $hasWarning ? 'Consult a veterinarian for medical concerns.' : null,
            'recommended_product_filters' => [],
        ];
    }
}
