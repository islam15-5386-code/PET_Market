<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'image_url')) {
                $table->string('image_url', 1024)->nullable()->after('images');
            }
            if (!Schema::hasColumn('products', 'thumbnail_url')) {
                $table->string('thumbnail_url', 1024)->nullable()->after('image_url');
            }
            if (!Schema::hasColumn('products', 'pet_type')) {
                $table->string('pet_type', 60)->nullable()->after('category_id');
            }
            if (!Schema::hasColumn('products', 'sub_category')) {
                $table->string('sub_category', 120)->nullable()->after('pet_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'thumbnail_url')) {
                $table->dropColumn('thumbnail_url');
            }
            if (Schema::hasColumn('products', 'image_url')) {
                $table->dropColumn('image_url');
            }
            if (Schema::hasColumn('products', 'sub_category')) {
                $table->dropColumn('sub_category');
            }
            if (Schema::hasColumn('products', 'pet_type')) {
                $table->dropColumn('pet_type');
            }
        });
    }
};

