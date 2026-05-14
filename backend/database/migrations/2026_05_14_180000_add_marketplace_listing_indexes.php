<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE INDEX IF NOT EXISTS products_category_id_idx ON products (category_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS products_pet_type_filter_idx ON products (pet_type)');
        DB::statement('CREATE INDEX IF NOT EXISTS products_price_filter_idx ON products (price)');
        DB::statement('CREATE INDEX IF NOT EXISTS products_is_available_filter_idx ON products (is_available)');
        DB::statement('CREATE INDEX IF NOT EXISTS products_created_at_filter_idx ON products (created_at)');
        DB::statement('CREATE INDEX IF NOT EXISTS products_rating_filter_idx ON products (rating)');

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE INDEX IF NOT EXISTS products_tags_gin_filter_idx ON products USING GIN (tags)');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS products_tags_gin_filter_idx');
        }

        DB::statement('DROP INDEX IF EXISTS products_rating_filter_idx');
        DB::statement('DROP INDEX IF EXISTS products_created_at_filter_idx');
        DB::statement('DROP INDEX IF EXISTS products_is_available_filter_idx');
        DB::statement('DROP INDEX IF EXISTS products_price_filter_idx');
        DB::statement('DROP INDEX IF EXISTS products_pet_type_filter_idx');
        DB::statement('DROP INDEX IF EXISTS products_category_id_idx');
    }
};
