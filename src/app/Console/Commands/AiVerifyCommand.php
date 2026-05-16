<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class AiVerifyCommand extends Command
{
    protected $signature = 'ai:verify';
    protected $description = 'Verify AI service connectivity and key AI API flows';

    public function handle(): int
    {
        $base = rtrim(config('services.ai_service.url', env('AI_SERVICE_URL', 'http://127.0.0.1:8001')), '/');
        $ok = true;

        $this->info("Checking AI base URL: {$base}");

        try {
            $health = Http::timeout(10)->get("{$base}/ai/health");
            $this->line('Health endpoint: ' . $health->status());
            if (!$health->successful()) {
                $ok = false;
            }
        } catch (\Throwable $e) {
            $this->error('Health endpoint failed: ' . $e->getMessage());
            $ok = false;
        }

        try {
            $search = Http::timeout(15)->post("{$base}/ai/product-search", ['query' => 'kitten food under 1000 bdt']);
            $this->line('AI search endpoint: ' . $search->status());
            if (!$search->successful()) {
                $ok = false;
            }
        } catch (\Throwable $e) {
            $this->error('AI search endpoint failed: ' . $e->getMessage());
            $ok = false;
        }

        try {
            $chat = Http::timeout(15)->post("{$base}/ai/pet-chatbot/message", [
                'message' => 'my cat is not eating',
                'session_id' => (string) \Illuminate\Support\Str::uuid(),
                'conversation_history' => [],
            ]);
            $this->line('Chatbot endpoint: ' . $chat->status());
            if (!$chat->successful()) {
                $ok = false;
            }
        } catch (\Throwable $e) {
            $this->error('Chatbot endpoint failed: ' . $e->getMessage());
            $ok = false;
        }

        try {
            $desc = Http::timeout(20)->post("{$base}/ai/product-description/generate", [
                'product_name' => 'Cat Food',
                'category' => 'Food',
                'pet_type' => 'Cat',
                'language' => 'English',
                'tone' => 'professional',
            ]);
            $this->line('Description endpoint: ' . $desc->status());
            if (!$desc->successful()) {
                $ok = false;
            }
        } catch (\Throwable $e) {
            $this->error('Description endpoint failed: ' . $e->getMessage());
            $ok = false;
        }

        if ($ok) {
            $this->info('AI verification passed.');
            return self::SUCCESS;
        }

        $this->warn('AI verification completed with failures.');
        return self::FAILURE;
    }
}
