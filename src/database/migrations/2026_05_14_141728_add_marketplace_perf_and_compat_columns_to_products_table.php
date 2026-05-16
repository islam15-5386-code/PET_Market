<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'age_group')) {
                $table->string('age_group', 80)->nullable()->after('pet_type');
            }
            if (!Schema::hasColumn('products', 'tags')) {
                $table->jsonb('tags')->nullable()->after('images');
            }
            if (!Schema::hasColumn('products', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('is_available');
            }
        });

        DB::statement('UPDATE products SET is_active = is_available WHERE is_active IS DISTINCT FROM is_available');

        DB::statement('CREATE INDEX IF NOT EXISTS products_pet_type_idx ON products (pet_type)');
        DB::statement('CREATE INDEX IF NOT EXISTS products_age_group_idx ON products (age_group)');
        DB::statement('CREATE INDEX IF NOT EXISTS products_rating_idx ON products (rating)');
        DB::statement('CREATE INDEX IF NOT EXISTS products_stock_quantity_idx ON products (stock_quantity)');
        DB::statement('CREATE INDEX IF NOT EXISTS products_created_at_idx ON products (created_at)');
        DB::statement('CREATE INDEX IF NOT EXISTS products_is_active_idx ON products (is_active)');
        DB::statement('CREATE INDEX IF NOT EXISTS products_available_location_price_idx ON products (is_available, location, price)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS products_pet_type_idx');
        DB::statement('DROP INDEX IF EXISTS products_age_group_idx');
        DB::statement('DROP INDEX IF EXISTS products_rating_idx');
        DB::statement('DROP INDEX IF EXISTS products_stock_quantity_idx');
        DB::statement('DROP INDEX IF EXISTS products_created_at_idx');
        DB::statement('DROP INDEX IF EXISTS products_is_active_idx');
        DB::statement('DROP INDEX IF EXISTS products_available_location_price_idx');

        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'is_active')) {
                $table->dropColumn('is_active');
            }
            if (Schema::hasColumn('products', 'tags')) {
                $table->dropColumn('tags');
            }
            if (Schema::hasColumn('products', 'age_group')) {
                $table->dropColumn('age_group');
            }
        });
    }
};
