<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'image_url')) {
                $table->text('image_url')->nullable()->after('images');
            }
            if (!Schema::hasColumn('products', 'pet_type')) {
                $table->string('pet_type', 60)->nullable()->after('brand');
            }
            if (!Schema::hasColumn('products', 'age_group')) {
                $table->string('age_group', 60)->nullable()->after('pet_type');
            }
            if (!Schema::hasColumn('products', 'tags')) {
                $table->jsonb('tags')->nullable()->after('age_group');
            }

        });

        DB::statement('CREATE INDEX IF NOT EXISTS products_created_at_idx ON products (created_at)');
        DB::statement('CREATE INDEX IF NOT EXISTS products_pet_type_idx ON products (pet_type)');
        DB::statement('CREATE INDEX IF NOT EXISTS products_is_available_active_idx ON products (is_available)');
        DB::statement('CREATE INDEX IF NOT EXISTS products_category_is_available_idx ON products (category_id, is_available)');

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE INDEX IF NOT EXISTS products_tags_gin_idx ON products USING GIN (tags)');
        }

        // Backfill image_url from the first images[] item if possible.
        DB::statement(
            "UPDATE products
             SET image_url = CASE
                 WHEN image_url IS NULL OR image_url = '' THEN COALESCE(images->>0, image_url)
                 ELSE image_url
             END"
        );
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS products_tags_gin_idx');
        }

        DB::statement('DROP INDEX IF EXISTS products_category_is_available_idx');

        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'tags')) {
                $table->dropColumn('tags');
            }
            if (Schema::hasColumn('products', 'age_group')) {
                $table->dropColumn('age_group');
            }
            if (Schema::hasColumn('products', 'pet_type')) {
                $table->dropColumn('pet_type');
            }
            if (Schema::hasColumn('products', 'image_url')) {
                $table->dropColumn('image_url');
            }
        });

        DB::statement('DROP INDEX IF EXISTS products_created_at_idx');
        DB::statement('DROP INDEX IF EXISTS products_pet_type_idx');
        DB::statement('DROP INDEX IF EXISTS products_is_available_active_idx');
    }
};
