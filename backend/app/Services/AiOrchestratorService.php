<?php

namespace App\Services;

use App\Models\AiRequest;
use Illuminate\Support\Facades\Http;

class AiOrchestratorService
{
    public function __construct(private readonly AiCacheService $cacheService)
    {
    }

    public function route(string $feature, array $input, ?int $userId = null, ?string $sessionId = null): array
    {
        $inputHash = hash('sha256', json_encode($input));

        $requestLog = [
            'user_id' => $userId,
            'feature' => $feature,
            'input_hash' => $inputHash,
            'strategy_used' => 'template',
            'prompt_tokens' => 0,
            'completion_tokens' => 0,
            'total_tokens' => 0,
            'status' => 'success',
            'error_message' => null,
        ];

        try {
            $base = rtrim(config('services.ai_service.url', env('AI_SERVICE_URL', 'http://127.0.0.1:8001')), '/');
            $response = Http::timeout(15)->post("{$base}/ai/route", [
                'feature' => $feature,
                'input' => $input,
                'user_id' => $userId,
                'session_id' => $sessionId,
            ]);

            if (!$response->successful()) {
                // Backward-compatible fallback for older AI service builds
                // that expose /ai/product-search but not /ai/route.
                if ($feature === 'product_search' && in_array($response->status(), [404, 422], true)) {
                    return $this->fallbackProductSearchRequest($base, $input, $requestLog);
                }

                throw new \RuntimeException('AI route request failed');
            }

            $payload = $response->json();
            $usage = $payload['token_usage'] ?? ['prompt_tokens' => 0, 'completion_tokens' => 0, 'total_tokens' => 0];
            $requestLog['strategy_used'] = $payload['strategy_used'] ?? 'template';
            $requestLog['prompt_tokens'] = (int) ($usage['prompt_tokens'] ?? 0);
            $requestLog['completion_tokens'] = (int) ($usage['completion_tokens'] ?? 0);
            $requestLog['total_tokens'] = (int) ($usage['total_tokens'] ?? 0);

            AiRequest::query()->create($requestLog);
            return $payload;
        } catch (\Throwable $e) {
            $requestLog['status'] = 'failed';
            $requestLog['error_message'] = $e->getMessage();
            AiRequest::query()->create($requestLog);

            return [
                'feature' => $feature,
                'strategy_used' => 'template',
                'result' => [],
                'token_usage' => ['prompt_tokens' => 0, 'completion_tokens' => 0, 'total_tokens' => 0],
                'cost_saved_reason' => 'AI service unavailable; fallback used.',
            ];
        }
    }

    private function fallbackProductSearchRequest(string $base, array $input, array $requestLog): array
    {
        $query = (string) ($input['query'] ?? '');
        if ($query === '') {
            throw new \RuntimeException('Missing query for product_search fallback');
        }

        $legacyResponse = Http::timeout(15)->post("{$base}/ai/product-search", [
            'query' => $query,
        ]);

        if (!$legacyResponse->successful()) {
            throw new \RuntimeException('AI product-search fallback request failed');
        }

        $legacyPayload = $legacyResponse->json();
        $normalized = [
            'feature' => 'product_search',
            'strategy_used' => 'rule_based',
            'result' => [
                'intent' => $legacyPayload['intent'] ?? 'product_search',
                'pet_type' => $legacyPayload['pet_type'] ?? null,
                'age_group' => $legacyPayload['age_group'] ?? null,
                'category' => $legacyPayload['category'] ?? null,
                'brand' => $legacyPayload['brand'] ?? null,
                'location' => $legacyPayload['location'] ?? null,
                'breed' => $legacyPayload['breed'] ?? null,
                'price_min' => $legacyPayload['price_min'] ?? ($legacyPayload['min_price'] ?? null),
                'price_max' => $legacyPayload['price_max'] ?? ($legacyPayload['max_price'] ?? null),
                'keywords' => $legacyPayload['keywords'] ?? [],
                'confidence' => $legacyPayload['confidence'] ?? 0.0,
            ],
            'token_usage' => ['prompt_tokens' => 0, 'completion_tokens' => 0, 'total_tokens' => 0],
            'cost_saved_reason' => 'Used /ai/product-search fallback for compatibility.',
        ];

        $requestLog['strategy_used'] = 'rule_based';
        AiRequest::query()->create($requestLog);

        return $normalized;
    }
}
