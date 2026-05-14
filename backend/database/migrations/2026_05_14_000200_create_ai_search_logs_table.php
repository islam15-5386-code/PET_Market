<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ai_search_logs')) {
            return;
        }

        Schema::create('ai_search_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('query');
            $table->string('detected_pet_type', 60)->nullable();
            $table->string('detected_category', 60)->nullable();
            $table->string('detected_age_group', 60)->nullable();
            $table->string('detected_brand', 120)->nullable();
            $table->decimal('detected_price_min', 10, 2)->nullable();
            $table->decimal('detected_price_max', 10, 2)->nullable();
            $table->decimal('confidence', 5, 4)->nullable();
            $table->unsignedInteger('total_results')->default(0);
            $table->timestamps();

            $table->index('user_id');
            $table->index('detected_pet_type');
            $table->index('detected_category');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_search_logs');
    }
};
