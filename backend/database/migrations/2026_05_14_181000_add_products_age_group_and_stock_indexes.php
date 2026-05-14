<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE INDEX IF NOT EXISTS products_age_group_filter_idx ON products (age_group)');
        DB::statement('CREATE INDEX IF NOT EXISTS products_stock_quantity_filter_idx ON products (stock_quantity)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS products_stock_quantity_filter_idx');
        DB::statement('DROP INDEX IF EXISTS products_age_group_filter_idx');
    }
};
