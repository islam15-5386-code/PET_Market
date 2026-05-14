<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('ai_generated_title', 255)->nullable()->after('description');
            $table->text('ai_generated_short_description')->nullable()->after('ai_generated_title');
            $table->text('ai_generated_long_description')->nullable()->after('ai_generated_short_description');
            $table->jsonb('ai_seo_keywords')->nullable()->after('ai_generated_long_description');
            $table->string('ai_meta_title', 255)->nullable()->after('ai_seo_keywords');
            $table->text('ai_meta_description')->nullable()->after('ai_meta_title');
            $table->jsonb('ai_generated_tags')->nullable()->after('ai_meta_description');
            $table->timestamp('ai_content_generated_at')->nullable()->after('ai_generated_tags');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'ai_generated_title',
                'ai_generated_short_description',
                'ai_generated_long_description',
                'ai_seo_keywords',
                'ai_meta_title',
                'ai_meta_description',
                'ai_generated_tags',
                'ai_content_generated_at',
            ]);
        });
    }
};
