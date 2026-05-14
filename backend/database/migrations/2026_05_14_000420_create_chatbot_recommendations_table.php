<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('chatbot_recommendations')) {
            return;
        }

        Schema::create('chatbot_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chatbot_message_id')->constrained('chatbot_messages')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('score', 6, 3)->nullable();
            $table->string('reason', 255)->nullable();
            $table->timestamps();

            $table->index(['chatbot_message_id', 'score']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chatbot_recommendations');
    }
};
