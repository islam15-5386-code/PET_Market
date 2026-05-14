<?php

namespace App\Services;

use App\Models\AiProductDescriptionLog;

class AiDescriptionService
{
    public function __construct(
        private readonly AiOrchestratorService $orchestrator,
        private readonly AiCacheService $cacheService,
    ) {
    }

    public function generate(array $input, ?int $userId = null): array
    {
        $cacheKey = 'desc:' . hash('sha256', json_encode($input));
        $cached = $this->cacheService->get('product_description', $cacheKey);
        if ($cached) {
            $this->log($userId, $input, $cached, 'cache', 'success');
            return $cached + ['strategy_used' => 'cache'];
        }

        $ai = $this->orchestrator->route('product_description', $input, $userId);
        $result = $ai['result'] ?? [];
        $strategy = $ai['strategy_used'] ?? 'template';
        $usage = $ai['token_usage'] ?? ['prompt_tokens' => 0, 'completion_tokens' => 0, 'total_tokens' => 0];

        $this->cacheService->put('product_description', $cacheKey, $input, $result, 86400 * 7);
        $this->log($userId, $input, $result, $strategy, 'success', $usage);

        $result['strategy_used'] = $strategy;
        $result['token_usage'] = $usage;
        return $result;
    }

    private function log(?int $userId, array $input, array $output, string $strategy, string $status, array $usage = []): void
    {
        AiProductDescriptionLog::query()->create([
            'user_id' => $userId,
            'product_id' => $input['product_id'] ?? null,
            'product_name' => $input['name'] ?? $input['product_name'] ?? 'Unknown',
            'category' => $input['category'] ?? null,
            'pet_type' => $input['pet_type'] ?? null,
            'input_payload' => $input,
            'generated_payload' => $output,
            'provider_name' => $output['provider_name'] ?? null,
            'model_name' => $output['model_name'] ?? null,
            'strategy_used' => $strategy,
            'prompt_tokens' => (int) ($usage['prompt_tokens'] ?? 0),
            'completion_tokens' => (int) ($usage['completion_tokens'] ?? 0),
            'total_tokens' => (int) ($usage['total_tokens'] ?? 0),
            'status' => $status,
        ]);
    }
}
