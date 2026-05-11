<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_product_descriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('title', 255);
            $table->text('description');
            $table->json('seo_keywords');
            $table->json('benefits');
            $table->string('source', 50);
            $table->string('prompt_hash', 64)->index();
            $table->timestamps();

            $table->unique(['product_id', 'prompt_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_product_descriptions');
    }
};

