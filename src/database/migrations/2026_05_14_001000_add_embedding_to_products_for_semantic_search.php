<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        /*
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('CREATE EXTENSION IF NOT EXISTS vector');

        // pgvector does not have a first-party Laravel schema type, use raw SQL.
        DB::statement('ALTER TABLE products ADD COLUMN IF NOT EXISTS embedding vector(384)');
        DB::statement('CREATE INDEX IF NOT EXISTS products_embedding_ivfflat_idx ON products USING ivfflat (embedding vector_cosine_ops) WITH (lists = 100)');
        */
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP INDEX IF EXISTS products_embedding_ivfflat_idx');
        DB::statement('ALTER TABLE products DROP COLUMN IF EXISTS embedding');
    }
};

