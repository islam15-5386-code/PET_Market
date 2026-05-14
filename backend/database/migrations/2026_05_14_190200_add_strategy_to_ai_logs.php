<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_search_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('ai_search_logs', 'strategy_used')) {
                $table->string('strategy_used', 40)->default('rule_based')->after('detected_price_max');
                $table->index('strategy_used');
            }
        });

        Schema::table('ai_product_description_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('ai_product_description_logs', 'strategy_used')) {
                $table->string('strategy_used', 40)->default('template')->after('model_name');
                $table->index('strategy_used');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ai_product_description_logs', function (Blueprint $table) {
            if (Schema::hasColumn('ai_product_description_logs', 'strategy_used')) {
                $table->dropIndex(['strategy_used']);
                $table->dropColumn('strategy_used');
            }
        });

        Schema::table('ai_search_logs', function (Blueprint $table) {
            if (Schema::hasColumn('ai_search_logs', 'strategy_used')) {
                $table->dropIndex(['strategy_used']);
                $table->dropColumn('strategy_used');
            }
        });
    }
};
