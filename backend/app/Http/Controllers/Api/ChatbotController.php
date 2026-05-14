<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AiChatbotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatbotController extends Controller
{
    public function __construct(private readonly AiChatbotService $chatbotService)
    {
    }

    public function message(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'min:2', 'max:2000'],
            'session_id' => ['nullable', 'string', 'max:100'],
        ]);

        $userId = null;
        try {
            $userId = auth('api')->id();
        } catch (\Throwable) {
            $userId = null;
        }

        $result = $this->chatbotService->message(
            $validated['message'],
            $validated['session_id'] ?? null,
            $userId,
        );

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }
}
