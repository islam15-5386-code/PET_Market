<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chatbot_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->uuid('session_uuid')->unique();
            $table->string('status', 20)->default('active');
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        Schema::create('chatbot_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chatbot_session_id')->constrained('chatbot_sessions')->cascadeOnDelete();
            $table->enum('sender', ['user', 'ai']);
            $table->text('message');
            $table->string('intent', 60)->nullable();
            $table->string('pet_type', 60)->nullable();
            $table->string('category', 60)->nullable();
            $table->string('age_group', 60)->nullable();
            $table->string('safety_level', 20)->nullable();
            $table->jsonb('ai_payload')->nullable();
            $table->timestamps();

            $table->index(['chatbot_session_id', 'created_at']);
            $table->index(['intent', 'pet_type', 'category']);
        });

        Schema::create('chatbot_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chatbot_message_id')->constrained('chatbot_messages')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('score', 6, 3)->nullable();
            $table->string('reason', 255)->nullable();
            $table->timestamps();

            $table->index(['chatbot_message_id', 'score']);
        });

        Schema::create('chatbot_training_data', function (Blueprint $table) {
            $table->id();
            $table->text('question');
            $table->text('answer');
            $table->string('intent', 60);
            $table->string('pet_type', 60)->nullable();
            $table->string('category', 60)->nullable();
            $table->string('age_group', 60)->nullable();
            $table->string('language', 30)->nullable();
            $table->string('source', 30)->default('manual');
            $table->boolean('is_approved')->default(true);
            $table->timestamps();

            $table->index(['is_approved', 'intent']);
        });

        Schema::create('chatbot_model_versions', function (Blueprint $table) {
            $table->id();
            $table->string('model_name', 100);
            $table->string('model_path', 255);
            $table->string('vectorizer_path', 255)->nullable();
            $table->unsignedInteger('training_rows_count')->default(0);
            $table->decimal('accuracy', 5, 4)->nullable();
            $table->string('status', 30)->default('trained');
            $table->timestamp('trained_at')->useCurrent();
            $table->timestamps();

            $table->index(['status', 'trained_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chatbot_model_versions');
        Schema::dropIfExists('chatbot_training_data');
        Schema::dropIfExists('chatbot_recommendations');
        Schema::dropIfExists('chatbot_messages');
        Schema::dropIfExists('chatbot_sessions');
    }
};
