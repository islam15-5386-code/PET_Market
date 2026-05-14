<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('chatbot_messages')) {
            return;
        }

        Schema::create('chatbot_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chatbot_session_id')->constrained('chatbot_sessions')->cascadeOnDelete();
            $table->enum('sender', ['user', 'ai']);
            $table->text('message');
            $table->string('intent', 80)->nullable();
            $table->string('pet_type', 50)->nullable();
            $table->string('category', 80)->nullable();
            $table->string('age_group', 50)->nullable();
            $table->string('safety_level', 30)->nullable();
            $table->jsonb('ai_payload')->nullable();
            $table->timestamps();

            $table->index(['chatbot_session_id', 'created_at']);
            $table->index('intent');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chatbot_messages');
    }
};
