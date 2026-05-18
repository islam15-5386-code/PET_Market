<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE INDEX IF NOT EXISTS products_available_latest_idx ON products (is_available, deleted_at, created_at DESC)');
        DB::statement('CREATE INDEX IF NOT EXISTS products_available_price_idx ON products (is_available, deleted_at, price)');
        DB::statement('CREATE INDEX IF NOT EXISTS products_available_rating_idx ON products (is_available, deleted_at, rating DESC, review_count DESC)');
        DB::statement('CREATE INDEX IF NOT EXISTS products_category_available_latest_idx ON products (category_id, is_available, deleted_at, created_at DESC)');
        DB::statement('CREATE INDEX IF NOT EXISTS products_low_stock_available_idx ON products (is_available, stock_quantity)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS products_low_stock_available_idx');
        DB::statement('DROP INDEX IF EXISTS products_category_available_latest_idx');
        DB::statement('DROP INDEX IF EXISTS products_available_rating_idx');
        DB::statement('DROP INDEX IF EXISTS products_available_price_idx');
        DB::statement('DROP INDEX IF EXISTS products_available_latest_idx');
    }
};
