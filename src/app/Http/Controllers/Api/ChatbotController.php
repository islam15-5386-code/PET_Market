<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ChatbotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatbotController extends Controller
{
    public function __construct(private readonly ChatbotService $chatbotService)
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
            $userId = optional(auth('api')->user())->id;
        } catch (\Throwable) {
            $userId = null;
        }
        $payload = $this->chatbotService->processMessage(
            $validated['message'],
            $validated['session_id'] ?? null,
            $userId,
        );

        return response()->json([
            'success' => true,
            'data' => $payload,
        ]);
    }
}
