<?php

namespace App\Services;

use App\Models\AiProductDescriptionLog;
use App\Models\Product;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class AiProductDescriptionService
{
    public function generate(array $validated, ?int $userId): array
    {
        $payload = $this->sanitizePayload($validated);

        $response = null;
        $error = null;

        try {
            $baseUrl = rtrim(config('services.ai_service.url', env('AI_SERVICE_URL')), '/');
            $timeout = (int) config('services.ai_service.description_timeout', 30);
            $response = Http::timeout(max(5, $timeout))->post("{$baseUrl}/ai/product-description/generate", $payload);
        } catch (ConnectionException $e) {
            $error = $e->getMessage();
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }

        if (!$response || !$response->successful()) {
            $generated = $this->templateFallback($payload);
            $this->log($userId, $payload, $generated, 'fallback', $error ?: 'AI service error');
            return $generated;
        }

        $generated = $response->json();
        $generated = $this->normalizeGenerated($generated, $payload);

        $this->log($userId, $payload, $generated, 'success', null);

        if (!empty($payload['product_id'])) {
            $this->persistToProduct((int) $payload['product_id'], $generated);
        }

        return $generated;
    }

    private function sanitizePayload(array $validated): array
    {
        $listFields = ['key_features', 'ingredients_or_materials'];

        foreach ($listFields as $listField) {
            if (isset($validated[$listField]) && is_string($validated[$listField])) {
                $validated[$listField] = array_values(array_filter(array_map('trim', explode(',', $validated[$listField]))));
            }
        }

        $validated['language'] = $validated['language'] ?? 'English';
        $validated['tone'] = $validated['tone'] ?? 'professional';

        return $validated;
    }

    private function normalizeGenerated(array $generated, array $payload): array
    {
        $fallback = $this->templateFallback($payload);
        $out = array_merge($fallback, $generated);

        $out['seo_keywords'] = array_values(array_slice((array) ($out['seo_keywords'] ?? []), 0, 5));
        $out['benefits'] = array_values(array_slice((array) ($out['benefits'] ?? []), 0, 3));
        $out['suggested_tags'] = array_values(array_slice((array) ($out['suggested_tags'] ?? []), 0, 10));
        $out['provider_name'] = (string) ($out['provider_name'] ?? 'fallback');
        $out['model_name'] = (string) ($out['model_name'] ?? 'template');

        $tokenUsage = Arr::get($out, 'token_usage', []);
        $out['token_usage'] = [
            'prompt_tokens' => (int) Arr::get($tokenUsage, 'prompt_tokens', 0),
            'completion_tokens' => (int) Arr::get($tokenUsage, 'completion_tokens', 0),
            'total_tokens' => (int) Arr::get($tokenUsage, 'total_tokens', 0),
        ];

        return $out;
    }

    private function templateFallback(array $payload): array
    {
        $name = $payload['product_name'] ?? 'Pet Product';
        $brand = $payload['brand'] ?? null;
        $category = $payload['category'] ?? 'Pet Supplies';
        $petType = $payload['pet_type'] ?? 'Pet';
        $ageGroup = $payload['age_group'] ?? null;
        $size = $payload['weight_or_size'] ?? null;
        $title = trim(implode(' ', array_filter([$brand, $name, $size ? "- {$size}" : null])));

        $short = "{$name} is a reliable {$category} item for {$petType}" . ($ageGroup ? " ({$ageGroup})" : '') . '.';
        $long = "{$short} Designed for Bangladesh pet marketplace customers, this product focuses on practical everyday use, quality, and balanced value.";

        return [
            'professional_product_title' => $title,
            'short_description' => $short,
            'long_description' => $long,
            'seo_keywords' => [strtolower("{$petType} {$category}"), strtolower($name), 'pet care bangladesh', strtolower($category), strtolower("{$petType} product")],
            'benefits' => ['Supports daily pet care', 'Easy to use', 'Balanced quality and value'],
            'care_instruction' => 'Store in a cool, dry place and keep packaging sealed after use.',
            'usage_instruction' => $payload['usage_instruction'] ?? 'Use as directed on product label.',
            'safety_warning' => 'Consult a veterinarian for medical concerns.',
            'meta_title' => $title,
            'meta_description' => substr($long, 0, 155),
            'suggested_tags' => array_values(array_filter([strtolower($petType), strtolower((string) $ageGroup), strtolower($category), 'pet-marketplace'])),
            'provider_name' => 'fallback',
            'model_name' => 'template',
            'token_usage' => [
                'prompt_tokens' => 0,
                'completion_tokens' => 0,
                'total_tokens' => 0,
            ],
        ];
    }

    private function persistToProduct(int $productId, array $generated): void
    {
        $product = Product::query()->find($productId);
        if (!$product) {
            return;
        }

        $product->update([
            'ai_generated_title' => $generated['professional_product_title'] ?? null,
            'ai_generated_short_description' => $generated['short_description'] ?? null,
            'ai_generated_long_description' => $generated['long_description'] ?? null,
            'ai_seo_keywords' => $generated['seo_keywords'] ?? [],
            'ai_meta_title' => $generated['meta_title'] ?? null,
            'ai_meta_description' => $generated['meta_description'] ?? null,
            'ai_generated_tags' => $generated['suggested_tags'] ?? [],
            'ai_content_generated_at' => now(),
        ]);
    }

    private function log(?int $userId, array $payload, array $generated, string $status, ?string $error): void
    {
        AiProductDescriptionLog::query()->create([
            'user_id' => $userId,
            'product_id' => isset($payload['product_id']) ? (int) $payload['product_id'] : null,
            'product_name' => (string) ($payload['product_name'] ?? ''),
            'category' => $payload['category'] ?? null,
            'pet_type' => $payload['pet_type'] ?? null,
            'input_payload' => $payload,
            'generated_payload' => $generated,
            'provider_name' => $generated['provider_name'] ?? null,
            'model_name' => $generated['model_name'] ?? null,
            'prompt_tokens' => Arr::get($generated, 'token_usage.prompt_tokens'),
            'completion_tokens' => Arr::get($generated, 'token_usage.completion_tokens'),
            'total_tokens' => Arr::get($generated, 'token_usage.total_tokens'),
            'status' => $status,
            'error_message' => $error,
        ]);
    }
}
