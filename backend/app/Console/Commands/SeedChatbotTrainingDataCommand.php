<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeedChatbotTrainingDataCommand extends Command
{
    protected $signature = 'seed:chatbot-training-data {--rows=1000000} {--chunk=5000} {--fresh : Truncate chatbot_training_data before insert}';
    protected $description = 'Seed large chatbot training dataset with common pet care Q/A patterns';

    private const PETS = ['cat', 'dog', 'bird', 'fish', 'rabbit'];
    private const PETS_BN = ['biral', 'kukur', 'pakhi', 'mach', 'khorgosh'];
    private const AGES = ['kitten', 'puppy', 'adult', 'senior'];

    private const INTENT_TEMPLATES = [
        'food_advice' => [
            'Which food is good for my %s?',
            'Best %s food under %d BDT',
            'amar %s er jonno valo food chai',
            '%s er jonno kon food valo?',
        ],
        'grooming_advice' => [
            'How often should I groom my %s?',
            '%s er grooming kotodin por por kora uchit?',
            'best shampoo for my %s',
            '%s er jonno kon shampoo valo?',
        ],
        'product_recommendation' => [
            'Recommend %s products under %d taka',
            'Need budget %s accessory',
            '%s er toy suggest koro',
            'Best %s cage in budget',
        ],
        'health_warning' => [
            'My %s is not eating, what should I do?',
            '%s vomiting and weak, help',
            'amar %s khabar khacche na, ki korbo?',
            '%s has fever and diarrhea',
        ],
        'emergency_warning' => [
            'My %s is bleeding and breathing problem',
            '%s unconscious after fall',
            '%s had seizure what now',
            '%s poison kheyeche',
        ],
        'general_pet_care' => [
            'How to take care of my %s daily?',
            'pet care routine for %s',
            'new %s owner tips',
            '%s er daily care ki?',
        ],
        'unknown' => [
            'hello',
            'can you help me',
            'what do you do',
            'thanks',
        ],
    ];

    private const ANSWERS = [
        'food_advice' => 'Choose balanced, age-appropriate food with quality protein and hydration support.',
        'grooming_advice' => 'Groom regularly based on coat type and keep skin, ears, and nails clean.',
        'product_recommendation' => 'Share pet type, age, and budget for better product suggestions.',
        'health_warning' => 'This may need medical attention. Consult a veterinarian for medical concerns.',
        'emergency_warning' => 'This may be serious. Please contact a veterinarian immediately.',
        'general_pet_care' => 'Maintain feeding schedule, clean water, hygiene, and daily activity routine.',
        'unknown' => 'Please share pet type and need so I can help better.',
    ];

    public function handle(): int
    {
        $rowsTarget = max(1, (int) $this->option('rows'));
        $chunkSize = max(100, min((int) $this->option('chunk'), 10000));

        if (DB::getDriverName() === 'sqlite') {
            $chunkSize = min($chunkSize, 120);
        }

        if ($this->option('fresh')) {
            DB::table('chatbot_training_data')->truncate();
            $this->warn('chatbot_training_data truncated.');
        }

        $existing = (int) DB::table('chatbot_training_data')->count();
        if ($existing >= $rowsTarget) {
            $this->info("Already has {$existing} rows (target: {$rowsTarget}). Nothing to do.");
            return self::SUCCESS;
        }

        $start = $existing + 1;
        $now = now();

        $this->info("Seeding chatbot_training_data to {$rowsTarget} rows...");

        for ($i = $start; $i <= $rowsTarget; $i += $chunkSize) {
            $end = min($i + $chunkSize - 1, $rowsTarget);
            $batch = [];

            for ($n = $i; $n <= $end; $n++) {
                $batch[] = $this->buildRow($n, $now);
            }

            DB::table('chatbot_training_data')->insert($batch);

            if ($end % 50000 === 0 || $end === $rowsTarget) {
                $this->line("  inserted up to {$end}");
            }
        }

        $final = (int) DB::table('chatbot_training_data')->count();
        $this->info("Done. chatbot_training_data total: {$final}");

        return self::SUCCESS;
    }

    private function buildRow(int $n, $now): array
    {
        $intentKeys = array_keys(self::INTENT_TEMPLATES);
        $intent = $intentKeys[$n % count($intentKeys)];

        $pet = self::PETS[$n % count(self::PETS)];
        $petBn = self::PETS_BN[$n % count(self::PETS_BN)];
        $age = self::AGES[$n % count(self::AGES)];

        $templates = self::INTENT_TEMPLATES[$intent];
        $tpl = $templates[$n % count($templates)];

        $budget = [500, 800, 1000, 1200, 1500, 2000][$n % 6];

        $question = $tpl;
        if (str_contains($question, '%d')) {
            $question = sprintf($question, $pet, $budget);
        } elseif (str_contains($question, '%s')) {
            if (str_contains($question, 'amar') || str_contains($question, 'er')) {
                $question = sprintf($question, $petBn);
            } elseif (str_contains($question, 'routine for')) {
                $question = sprintf($question, $age);
            } else {
                $question = sprintf($question, $pet);
            }
        }

        $category = match ($intent) {
            'food_advice' => 'food',
            'grooming_advice' => 'grooming',
            'product_recommendation' => ['toy', 'accessory', 'food', 'cage'][$n % 4],
            'health_warning', 'emergency_warning' => 'medicine',
            'general_pet_care' => 'accessory',
            default => null,
        };

        $language = match ($n % 3) {
            0 => 'English',
            1 => 'Bangla-English mixed',
            default => 'Bangla-English mixed',
        };

        return [
            'question' => $question,
            'answer' => self::ANSWERS[$intent],
            'intent' => $intent,
            'pet_type' => $intent === 'unknown' ? null : $pet,
            'category' => $category,
            'age_group' => $intent === 'unknown' ? null : $age,
            'language' => $language,
            'source' => 'manual',
            'is_approved' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }
}
