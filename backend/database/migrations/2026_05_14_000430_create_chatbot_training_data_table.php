<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('chatbot_training_data')) {
            return;
        }

        Schema::create('chatbot_training_data', function (Blueprint $table) {
            $table->id();
            $table->text('question');
            $table->text('answer');
            $table->string('intent', 80);
            $table->string('pet_type', 50)->nullable();
            $table->string('category', 80)->nullable();
            $table->string('age_group', 50)->nullable();
            $table->string('language', 30)->nullable();
            $table->string('source', 30)->default('manual');
            $table->boolean('is_approved')->default(true);
            $table->timestamps();

            $table->index(['is_approved', 'intent']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chatbot_training_data');
    }
};
