<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class AiChatbotClientService
{
    public function ask(array $payload): array
    {
        $base = rtrim(config('services.ai_service.url', env('AI_SERVICE_URL', 'http://127.0.0.1:8001')), '/');
        $timeout = (int) config('services.ai_service.chatbot_timeout', 30);

        $response = Http::timeout(max(5, $timeout))->post("{$base}/chatbot/message", $payload);

        if (!$response->successful()) {
            throw new \RuntimeException('AI chatbot service unavailable');
        }

        return $response->json();
    }
}
