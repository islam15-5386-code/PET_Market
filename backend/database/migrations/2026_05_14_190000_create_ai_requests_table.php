<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ai_requests')) {
            return;
        }

        Schema::create('ai_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('feature', 80);
            $table->string('input_hash', 128);
            $table->string('strategy_used', 40)->default('template');
            $table->unsignedInteger('prompt_tokens')->default(0);
            $table->unsignedInteger('completion_tokens')->default(0);
            $table->unsignedInteger('total_tokens')->default(0);
            $table->string('status', 30)->default('success');
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['feature', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index('input_hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_requests');
    }
};
