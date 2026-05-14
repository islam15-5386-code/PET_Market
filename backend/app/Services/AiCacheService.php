<?php

namespace App\Services;

use App\Models\AiCache;
use Illuminate\Support\Carbon;

class AiCacheService
{
    public function get(string $feature, string $cacheKey): ?array
    {
        $row = AiCache::query()
            ->where('feature', $feature)
            ->where('cache_key', $cacheKey)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->first();

        return $row?->output_payload;
    }

    public function put(string $feature, string $cacheKey, array $input, array $output, ?int $ttlSeconds = null): void
    {
        $expiresAt = $ttlSeconds ? Carbon::now()->addSeconds($ttlSeconds) : null;

        AiCache::query()->updateOrCreate(
            ['cache_key' => $cacheKey],
            [
                'feature' => $feature,
                'input_payload' => $input,
                'output_payload' => $output,
                'expires_at' => $expiresAt,
            ]
        );
    }
}
