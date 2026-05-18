<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AiChatbotClientService
{
    private const SYSTEM_INSTRUCTION = <<<'TEXT'
You are PetCare AI Assistant. You help users with pet food, grooming, basic care, safe product suggestions, and ecommerce product guidance. You do not diagnose diseases or replace veterinarians. For serious symptoms, emergency signs, poisoning, injury, breathing problems, continuous vomiting, seizures, or severe pain, advise the user to contact a licensed veterinarian immediately.
TEXT;

    public function ask(array $payload): array
    {
        $message = trim((string) ($payload['message'] ?? ''));
        $localContext = $payload['local_context'] ?? $this->localContext($message);

        if (($localContext['safety_level'] ?? 'safe') === 'emergency') {
            return $this->fallbackResponse($message, $localContext);
        }

        $provider = $this->resolveProvider();
        if (!$provider) {
            return $this->fallbackResponse($message, $localContext);
        }

        try {
            $raw = match ($provider) {
                'openai' => $this->askOpenAi($payload, $localContext),
                default => $this->askGemini($payload, $localContext),
            };

            return $this->normalizeProviderPayload($raw, $localContext, $provider);
        } catch (\Throwable) {
            return $this->fallbackResponse($message, $localContext, apiUnavailable: true);
        }
    }

    public function localContext(string $message): array
    {
        $lower = Str::lower($message);
        $language = $this->detectLanguage($message);
        $safetyLevel = $this->isEmergency($lower) ? 'emergency' : ($this->mentionsHealthConcern($lower) ? 'caution' : 'safe');

        $filters = [
            'pet_type' => $this->detectPetType($lower),
            'category' => $this->detectCategory($lower),
            'age_group' => $this->detectAgeGroup($lower),
            'price_min' => null,
            'price_max' => $this->detectMaxPrice($lower),
            'location' => $this->detectLocation($lower),
        ];

        $filters = array_filter($filters, fn ($value) => $value !== null && $value !== '');
        $wantsProducts = $this->wantsProductRecommendations($lower, $filters);

        return [
            'language' => $language,
            'safety_level' => $safetyLevel,
            'vet_warning' => $safetyLevel === 'emergency'
                ? $this->vetWarning($language)
                : ($safetyLevel === 'caution' ? $this->cautionWarning($language) : null),
            'intent' => $this->intentFrom($lower, $safetyLevel, $wantsProducts, $filters),
            'recommended_product_filters' => $filters,
            'wants_product_recommendations' => $wantsProducts,
        ];
    }

    public function fallbackResponse(string $message, array $localContext = [], bool $apiUnavailable = false): array
    {
        $localContext = $localContext ?: $this->localContext($message);
        $language = $localContext['language'] ?? $this->detectLanguage($message);
        $safetyLevel = $localContext['safety_level'] ?? 'safe';
        $filters = $localContext['recommended_product_filters'] ?? [];
        $useBangla = $this->prefersBangla($language);

        if ($safetyLevel === 'emergency') {
            $reply = $useBangla
                ? 'এটা জরুরি হতে পারে। রক্তপাত, শ্বাসকষ্ট, বিষক্রিয়া, খিঁচুনি, বারবার বমি, অজ্ঞান হয়ে যাওয়া বা তীব্র ব্যথা থাকলে এখনই একজন লাইসেন্সপ্রাপ্ত ভেটেরিনারিয়ানের সাথে যোগাযোগ করুন। আমি রোগ নির্ণয় বা ওষুধের ডোজ দিতে পারি না।'
                : 'This may be serious. If there is bleeding, breathing trouble, poisoning, seizures, repeated vomiting, unconsciousness, or severe pain, contact a licensed veterinarian immediately. I cannot diagnose disease or prescribe medicine dosage.';

            return $this->responseShape($reply, 'emergency_warning', $filters, $localContext, 'local_safety');
        }

        if ($apiUnavailable) {
            $reply = $useBangla
                ? 'দুঃখিত, এখন AI সার্ভিসে কানেক্ট করতে সমস্যা হচ্ছে। তবুও আমি সাধারণ pet food, grooming, care routine এবং product guidance দিতে পারি। আপনার pet type, age আর budget বললে আমি দোকানের available products থেকে suggestion দেখাব।'
                : "Sorry, I'm having trouble connecting to AI right now. I can still help with general pet food, grooming, care routines, and product guidance. Share your pet type, age, and budget and I will suggest available products from the store.";

            return $this->responseShape($reply, $localContext['intent'] ?? 'unknown', $filters, $localContext, 'local_fallback');
        }

        if (($localContext['wants_product_recommendations'] ?? false) || !empty($filters)) {
            $pet = $filters['pet_type'] ?? 'your pet';
            $category = $filters['category'] ?? 'products';
            $budget = isset($filters['price_max']) ? ' within your budget' : '';
            $reply = $useBangla
                ? "আমি {$pet} এর জন্য {$category} সম্পর্কিত নিরাপদ product suggestion দেখাচ্ছি। product card থেকে price, stock, brand ও details দেখে নিন। গুরুতর অসুস্থতার লক্ষণ থাকলে ভেটের সাথে যোগাযোগ করুন।"
                : "I found safe {$category} options for {$pet}{$budget}. Check the product cards for price, stock, brand, and details. For serious symptoms, contact a veterinarian.";

            return $this->responseShape($reply, 'product_recommendation', $filters, $localContext, 'local_fallback');
        }

        $reply = $useBangla
            ? 'আমি pet food, grooming, daily care, behavior tips এবং store product suggestion নিয়ে সাহায্য করতে পারি। আপনার pet type, বয়স, budget বা যে সমস্যাটা জানতে চান সেটা লিখুন।'
            : 'I can help with pet food, grooming, daily care, behavior tips, and store product suggestions. Tell me your pet type, age, budget, or what you need help with.';

        return $this->responseShape($reply, $localContext['intent'] ?? 'general_pet_care', $filters, $localContext, 'local_fallback');
    }

    private function askGemini(array $payload, array $localContext): array
    {
        $key = (string) config('services.ai_chat.gemini_key');
        $model = (string) config('services.ai_chat.gemini_model', 'gemini-1.5-flash');
        $timeout = (int) config('services.ai_chat.timeout', 20);

        $response = Http::timeout(max(5, $timeout))
            ->retry(1, 250)
            ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$key}", [
                'systemInstruction' => [
                    'parts' => [['text' => self::SYSTEM_INSTRUCTION]],
                ],
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [['text' => $this->buildPrompt($payload, $localContext)]],
                    ],
                ],
                'generationConfig' => [
                    'temperature' => 0.35,
                    'maxOutputTokens' => 650,
                    'responseMimeType' => 'application/json',
                ],
            ]);

        if (!$response->successful()) {
            throw new \RuntimeException('Gemini request failed');
        }

        $text = collect($response->json('candidates.0.content.parts', []))
            ->pluck('text')
            ->filter()
            ->implode("\n");

        return $this->decodeJson($text);
    }

    private function askOpenAi(array $payload, array $localContext): array
    {
        $key = (string) config('services.ai_chat.openai_key');
        $model = (string) config('services.ai_chat.openai_model', 'gpt-4o-mini');
        $timeout = (int) config('services.ai_chat.timeout', 20);

        $response = Http::withToken($key)
            ->timeout(max(5, $timeout))
            ->retry(1, 250)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $model,
                'temperature' => 0.35,
                'max_tokens' => 650,
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    ['role' => 'system', 'content' => self::SYSTEM_INSTRUCTION],
                    ['role' => 'user', 'content' => $this->buildPrompt($payload, $localContext)],
                ],
            ]);

        if (!$response->successful()) {
            throw new \RuntimeException('OpenAI request failed');
        }

        return $this->decodeJson((string) $response->json('choices.0.message.content', ''));
    }

    private function buildPrompt(array $payload, array $localContext): string
    {
        $message = (string) ($payload['message'] ?? '');
        $history = collect($payload['conversation_history'] ?? [])
            ->take(-8)
            ->map(fn ($item) => [
                'sender' => $item['sender'] ?? 'user',
                'message' => Str::limit((string) ($item['message'] ?? ''), 400),
            ])
            ->values()
            ->all();

        $products = collect($payload['product_context'] ?? [])
            ->take(5)
            ->values()
            ->all();

        return json_encode([
            'task' => 'Reply as a safe ecommerce pet-care chatbot. Return JSON only.',
            'required_json_schema' => [
                'reply' => 'string',
                'intent' => 'food_advice|grooming_advice|product_recommendation|health_warning|emergency_warning|general_pet_care|store_question|unknown',
                'pet_type' => 'dog|cat|bird|fish|rabbit|mixed|null',
                'category' => 'food|grooming|toys|accessories|health|beds|aquatics|null',
                'age_group' => 'puppy|kitten|junior|adult|senior|all ages|null',
                'safety_level' => 'safe|caution|emergency',
                'vet_warning' => 'string|null',
                'wants_product_recommendations' => 'boolean',
                'recommended_product_filters' => [
                    'pet_type' => 'string|null',
                    'category' => 'string|null',
                    'age_group' => 'string|null',
                    'price_min' => 'number|null',
                    'price_max' => 'number|null',
                    'location' => 'string|null',
                ],
            ],
            'rules' => [
                'Answer in Bangla if the user writes Bangla, English if English, and natural mixed Bangla-English if mixed.',
                'Use products from product_context first when recommending or comparing products.',
                'Do not diagnose disease. Do not prescribe medicine dosage.',
                'For emergency signs, tell the user to contact a licensed veterinarian immediately.',
                'Do not suggest unsafe foods such as chocolate, grapes, raisins, onions, alcohol, xylitol, or cooked bones.',
            ],
            'local_context' => $localContext,
            'product_context' => $products,
            'conversation_history' => $history,
            'user_message' => $message,
        ], JSON_UNESCAPED_SLASHES);
    }

    private function normalizeProviderPayload(array $payload, array $localContext, string $provider): array
    {
        $filters = array_filter(array_merge(
            $localContext['recommended_product_filters'] ?? [],
            Arr::only($payload['recommended_product_filters'] ?? [], ['pet_type', 'category', 'age_group', 'price_min', 'price_max', 'location'])
        ), fn ($value) => $value !== null && $value !== '');

        $safetyLevel = in_array($payload['safety_level'] ?? null, ['safe', 'caution', 'emergency'], true)
            ? $payload['safety_level']
            : ($localContext['safety_level'] ?? 'safe');

        if ($safetyLevel === 'emergency') {
            $payload['vet_warning'] = $payload['vet_warning'] ?: ($localContext['vet_warning'] ?? $this->vetWarning($localContext['language'] ?? 'en'));
        }

        return [
            'reply' => trim((string) ($payload['reply'] ?? '')) ?: $this->fallbackResponse('', $localContext)['reply'],
            'intent' => (string) ($payload['intent'] ?? $localContext['intent'] ?? 'unknown'),
            'pet_type' => $payload['pet_type'] ?? ($filters['pet_type'] ?? null),
            'category' => $payload['category'] ?? ($filters['category'] ?? null),
            'age_group' => $payload['age_group'] ?? ($filters['age_group'] ?? null),
            'safety_level' => $safetyLevel,
            'vet_warning' => $payload['vet_warning'] ?? ($localContext['vet_warning'] ?? null),
            'wants_product_recommendations' => (bool) ($payload['wants_product_recommendations'] ?? $localContext['wants_product_recommendations'] ?? false),
            'recommended_product_filters' => $filters,
            'provider' => $provider,
        ];
    }

    private function responseShape(string $reply, string $intent, array $filters, array $localContext, string $provider): array
    {
        return [
            'reply' => $reply,
            'intent' => $intent,
            'pet_type' => $filters['pet_type'] ?? null,
            'category' => $filters['category'] ?? null,
            'age_group' => $filters['age_group'] ?? null,
            'safety_level' => $localContext['safety_level'] ?? 'safe',
            'vet_warning' => $localContext['vet_warning'] ?? null,
            'wants_product_recommendations' => (bool) ($localContext['wants_product_recommendations'] ?? !empty($filters)),
            'recommended_product_filters' => $filters,
            'provider' => $provider,
        ];
    }

    private function resolveProvider(): ?string
    {
        $preferred = Str::lower((string) config('services.ai_chat.provider', 'gemini'));
        $geminiKey = (string) config('services.ai_chat.gemini_key');
        $openAiKey = (string) config('services.ai_chat.openai_key');

        if ($preferred === 'openai' && $this->hasUsableKey($openAiKey)) {
            return 'openai';
        }

        if ($preferred === 'gemini' && $this->hasUsableKey($geminiKey)) {
            return 'gemini';
        }

        if ($this->hasUsableKey($geminiKey)) {
            return 'gemini';
        }

        if ($this->hasUsableKey($openAiKey)) {
            return 'openai';
        }

        return null;
    }

    private function hasUsableKey(?string $key): bool
    {
        $key = trim((string) $key);
        return $key !== ''
            && !str_contains($key, 'your_')
            && !str_contains($key, 'placeholder')
            && !str_contains($key, 'api_key_here');
    }

    private function decodeJson(string $text): array
    {
        $text = trim($text);
        if (preg_match('/\{.*\}/s', $text, $matches)) {
            $text = $matches[0];
        }

        $decoded = json_decode($text, true);
        if (!is_array($decoded)) {
            throw new \RuntimeException('Provider returned invalid JSON');
        }

        return $decoded;
    }

    private function detectLanguage(string $message): string
    {
        $hasBangla = (bool) preg_match('/[\x{0980}-\x{09FF}]/u', $message);
        $hasEnglish = (bool) preg_match('/[A-Za-z]/', $message);

        if ($hasBangla && $hasEnglish) {
            return 'mixed';
        }

        return $hasBangla ? 'bn' : 'en';
    }

    private function prefersBangla(string $language): bool
    {
        return in_array($language, ['bn', 'mixed'], true);
    }

    private function detectPetType(string $lower): ?string
    {
        return match (true) {
            str_contains($lower, 'dog') || str_contains($lower, 'puppy') || str_contains($lower, 'kukur') => 'dog',
            str_contains($lower, 'cat') || str_contains($lower, 'kitten') || str_contains($lower, 'biral') => 'cat',
            str_contains($lower, 'bird') || str_contains($lower, 'parrot') || str_contains($lower, 'pakhi') => 'bird',
            str_contains($lower, 'fish') || str_contains($lower, 'aquarium') || str_contains($lower, 'mach') => 'fish',
            str_contains($lower, 'rabbit') || str_contains($lower, 'hamster') || str_contains($lower, 'guinea') || str_contains($lower, 'khorgosh') => 'rabbit',
            default => null,
        };
    }

    private function detectCategory(string $lower): ?string
    {
        return match (true) {
            str_contains($lower, 'food') || str_contains($lower, 'meal') || str_contains($lower, 'khabar') || str_contains($lower, 'feed') => 'food',
            str_contains($lower, 'groom') || str_contains($lower, 'shampoo') || str_contains($lower, 'brush') || str_contains($lower, 'bath') => 'grooming',
            str_contains($lower, 'toy') || str_contains($lower, 'play') => 'toys',
            str_contains($lower, 'bed') || str_contains($lower, 'sleep') => 'beds',
            str_contains($lower, 'collar') || str_contains($lower, 'leash') || str_contains($lower, 'harness') || str_contains($lower, 'cage') || str_contains($lower, 'accessory') => 'accessories',
            str_contains($lower, 'aquarium') || str_contains($lower, 'filter') || str_contains($lower, 'fish tank') => 'aquatics',
            str_contains($lower, 'vitamin') || str_contains($lower, 'flea') || str_contains($lower, 'tick') || str_contains($lower, 'medicine') || str_contains($lower, 'health') => 'health',
            default => null,
        };
    }

    private function detectAgeGroup(string $lower): ?string
    {
        return match (true) {
            str_contains($lower, 'puppy') => 'puppy',
            str_contains($lower, 'kitten') => 'kitten',
            str_contains($lower, 'junior') => 'junior',
            str_contains($lower, 'senior') || str_contains($lower, 'old') => 'senior',
            str_contains($lower, 'adult') => 'adult',
            default => null,
        };
    }

    private function detectMaxPrice(string $lower): ?float
    {
        if (preg_match('/(?:under|below|within|budget|less than|৳|bdt|taka|tk)\s*(\d{2,7})/i', $lower, $matches)) {
            return (float) $matches[1];
        }

        if (preg_match('/(\d{2,7})\s*(?:bdt|taka|tk|টাকা)/i', $lower, $matches)) {
            return (float) $matches[1];
        }

        return null;
    }

    private function detectLocation(string $lower): ?string
    {
        foreach (['dhaka', 'chattogram', 'chittagong', 'sylhet', 'rajshahi', 'khulna', 'barishal', 'rangpur', 'mymensingh', 'gazipur'] as $location) {
            if (str_contains($lower, $location)) {
                return $location === 'chittagong' ? 'Chattogram' : Str::title($location);
            }
        }

        return null;
    }

    private function wantsProductRecommendations(string $lower, array $filters): bool
    {
        return isset($filters['category'])
            || isset($filters['price_min'])
            || isset($filters['price_max'])
            || isset($filters['location'])
            || str_contains($lower, 'recommend')
            || str_contains($lower, 'suggest')
            || str_contains($lower, 'compare')
            || str_contains($lower, 'buy')
            || str_contains($lower, 'product')
            || str_contains($lower, 'under')
            || str_contains($lower, 'budget')
            || str_contains($lower, 'konta valo')
            || str_contains($lower, 'suggest koro');
    }

    private function intentFrom(string $lower, string $safetyLevel, bool $wantsProducts, array $filters): string
    {
        if ($safetyLevel === 'emergency') {
            return 'emergency_warning';
        }
        if ($safetyLevel === 'caution') {
            return 'health_warning';
        }
        if ($wantsProducts) {
            return 'product_recommendation';
        }
        if (($filters['category'] ?? null) === 'food') {
            return 'food_advice';
        }
        if (($filters['category'] ?? null) === 'grooming') {
            return 'grooming_advice';
        }
        if (str_contains($lower, 'routine') || str_contains($lower, 'care') || str_contains($lower, 'behavior')) {
            return 'general_pet_care';
        }

        return 'unknown';
    }

    private function mentionsHealthConcern(string $lower): bool
    {
        foreach (['vomit', 'not eating', 'diarrhea', 'fever', 'sick', 'pain', 'weak', 'khacche na', 'osustho'] as $term) {
            if (str_contains($lower, $term)) {
                return true;
            }
        }

        return false;
    }

    private function isEmergency(string $lower): bool
    {
        foreach (['bleeding', 'blood', 'breathing problem', 'cannot breathe', 'poison', 'xylitol', 'seizure', 'unconscious', 'injury', 'severe pain', 'continuous vomiting', 'bar bar vomit', 'bish', 'shash', 'rokto'] as $term) {
            if (str_contains($lower, $term)) {
                return true;
            }
        }

        return false;
    }

    private function vetWarning(string $language): string
    {
        return $this->prefersBangla($language)
            ? 'জরুরি লক্ষণ থাকলে এখনই একজন লাইসেন্সপ্রাপ্ত ভেটেরিনারিয়ানের সাথে যোগাযোগ করুন।'
            : 'For emergency signs, contact a licensed veterinarian immediately.';
    }

    private function cautionWarning(string $language): string
    {
        return $this->prefersBangla($language)
            ? 'লক্ষণ চলতে থাকলে বা খারাপ হলে ভেটেরিনারিয়ানের সাথে যোগাযোগ করুন।'
            : 'If symptoms continue or worsen, contact a veterinarian.';
    }
}
