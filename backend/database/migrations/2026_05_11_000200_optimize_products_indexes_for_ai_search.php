<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE INDEX IF NOT EXISTS products_price_idx2 ON products (price)');
        DB::statement('CREATE INDEX IF NOT EXISTS products_category_id_idx2 ON products (category_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS products_stock_quantity_idx ON products (stock_quantity)');
        DB::statement('CREATE INDEX IF NOT EXISTS products_brand_idx ON products (brand)');
        DB::statement('CREATE INDEX IF NOT EXISTS products_location_idx2 ON products (location)');
        DB::statement('CREATE INDEX IF NOT EXISTS products_created_at_idx ON products (created_at)');

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');
            DB::statement('ALTER TABLE products ADD COLUMN IF NOT EXISTS search_vector tsvector');
            DB::statement("
                UPDATE products
                SET search_vector = to_tsvector(
                    'simple',
                    coalesce(name,'') || ' ' ||
                    coalesce(description,'') || ' ' ||
                    coalesce(brand,'') || ' ' ||
                    coalesce(location,'')
                )
            ");
            DB::statement('CREATE INDEX IF NOT EXISTS products_search_vector_gin_idx ON products USING GIN(search_vector)');
            DB::statement('CREATE INDEX IF NOT EXISTS products_name_trgm_idx2 ON products USING GIN(name gin_trgm_ops)');
        }
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS products_price_idx2');
        DB::statement('DROP INDEX IF EXISTS products_category_id_idx2');
        DB::statement('DROP INDEX IF EXISTS products_stock_quantity_idx');
        DB::statement('DROP INDEX IF EXISTS products_brand_idx');
        DB::statement('DROP INDEX IF EXISTS products_location_idx2');
        DB::statement('DROP INDEX IF EXISTS products_created_at_idx');

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS products_search_vector_gin_idx');
            DB::statement('DROP INDEX IF EXISTS products_name_trgm_idx2');
            DB::statement('ALTER TABLE products DROP COLUMN IF EXISTS search_vector');
        }
    }
};

