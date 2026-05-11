<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            if (!Schema::hasColumn('categories', 'image_url')) {
                $table->string('image_url', 1024)->nullable()->after('icon');
            }
        });

        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'brand')) {
                $table->string('brand', 120)->nullable()->after('location');
            }
            if (!Schema::hasColumn('products', 'sku')) {
                $table->string('sku', 80)->nullable()->after('brand');
            }
            if (!Schema::hasColumn('products', 'rating')) {
                $table->decimal('rating', 3, 2)->default(0)->after('sku');
            }
            if (!Schema::hasColumn('products', 'review_count')) {
                $table->unsignedInteger('review_count')->default(0)->after('rating');
            }
        });

        Schema::table('products', function (Blueprint $table) {
            $table->index(['category_id', 'price'], 'products_category_price_idx');
            $table->index(['category_id', 'location'], 'products_category_location_idx');
        });

        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS products_slug_unique_idx ON products (slug)');
        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS products_sku_unique_idx ON products (sku)');
        DB::statement('CREATE INDEX IF NOT EXISTS products_name_idx ON products (name)');
        DB::statement('CREATE INDEX IF NOT EXISTS products_location_idx ON products (location)');
        DB::statement('CREATE INDEX IF NOT EXISTS products_price_idx ON products (price)');
        DB::statement('CREATE INDEX IF NOT EXISTS products_category_idx ON products (category_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS products_is_available_idx ON products (is_available)');

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');
            DB::statement('CREATE INDEX IF NOT EXISTS products_name_trgm_idx ON products USING GIN (name gin_trgm_ops)');
            DB::statement('CREATE INDEX IF NOT EXISTS products_name_description_tsv_idx ON products USING GIN (to_tsvector(\'simple\', coalesce(name,\'\') || \' \' || coalesce(description,\'\')))');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS products_name_description_tsv_idx');
            DB::statement('DROP INDEX IF EXISTS products_name_trgm_idx');
        }
        DB::statement('DROP INDEX IF EXISTS products_slug_unique_idx');
        DB::statement('DROP INDEX IF EXISTS products_sku_unique_idx');
        DB::statement('DROP INDEX IF EXISTS products_name_idx');
        DB::statement('DROP INDEX IF EXISTS products_location_idx');
        DB::statement('DROP INDEX IF EXISTS products_price_idx');
        DB::statement('DROP INDEX IF EXISTS products_category_idx');
        DB::statement('DROP INDEX IF EXISTS products_is_available_idx');

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_category_price_idx');
            $table->dropIndex('products_category_location_idx');

            if (Schema::hasColumn('products', 'review_count')) {
                $table->dropColumn('review_count');
            }
            if (Schema::hasColumn('products', 'rating')) {
                $table->dropColumn('rating');
            }
            if (Schema::hasColumn('products', 'sku')) {
                $table->dropColumn('sku');
            }
            if (Schema::hasColumn('products', 'brand')) {
                $table->dropColumn('brand');
            }
        });

        Schema::table('categories', function (Blueprint $table) {
            if (Schema::hasColumn('categories', 'image_url')) {
                $table->dropColumn('image_url');
            }
        });
    }
};

