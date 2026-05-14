<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ai_cache')) {
            return;
        }

        Schema::create('ai_cache', function (Blueprint $table) {
            $table->id();
            $table->string('feature', 80);
            $table->string('cache_key', 191)->unique();
            $table->jsonb('input_payload');
            $table->jsonb('output_payload');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['feature', 'created_at']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_cache');
    }
};
