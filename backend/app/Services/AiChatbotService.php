<?php

namespace App\Services;

class AiChatbotService
{
    public function __construct(private readonly ChatbotService $chatbotService)
    {
    }

    public function message(string $message, ?string $sessionId, ?int $userId): array
    {
        return $this->chatbotService->handle($message, $sessionId, $userId);
    }
}
