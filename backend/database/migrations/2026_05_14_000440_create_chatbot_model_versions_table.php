<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('chatbot_model_versions')) {
            return;
        }

        Schema::create('chatbot_model_versions', function (Blueprint $table) {
            $table->id();
            $table->string('model_name', 120);
            $table->string('model_path', 255);
            $table->string('vectorizer_path', 255)->nullable();
            $table->unsignedInteger('training_rows_count')->default(0);
            $table->decimal('accuracy', 5, 4)->nullable();
            $table->string('status', 30);
            $table->timestamp('trained_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'trained_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chatbot_model_versions');
    }
};
