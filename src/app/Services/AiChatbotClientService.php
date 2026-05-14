<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class AiChatbotClientService
{
    public function sendMessage(array $payload): array
    {
        $baseUrl = rtrim(config('services.ai_service.url', env('AI_SERVICE_URL', 'http://127.0.0.1:8001')), '/');
        $timeout = (int) env('AI_CHATBOT_TIMEOUT', 20);

        $response = Http::timeout(max(5, $timeout))->post("{$baseUrl}/chatbot/message", $payload);

        if (!$response->successful()) {
            throw new \RuntimeException('AI chatbot service failed with status ' . $response->status());
        }

        $json = $response->json();
        if (!is_array($json) || empty($json['reply'])) {
            throw new \RuntimeException('AI chatbot returned invalid payload.');
        }

        return $json;
    }
}
