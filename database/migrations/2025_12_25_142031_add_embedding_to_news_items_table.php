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
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        Schema::table('news_items', function (Blueprint $table) {
            $table->boolean('is_embedded')->default(false)->index();
        });

        DB::statement('ALTER TABLE news_items ADD COLUMN embedding vector(1536)');
        DB::statement('CREATE INDEX news_items_embedding_idx ON news_items USING hnsw (embedding vector_cosine_ops)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP INDEX IF EXISTS news_items_embedding_idx');
        DB::statement('ALTER TABLE news_items DROP COLUMN IF EXISTS embedding');

        Schema::table('news_items', function (Blueprint $table) {
            $table->dropColumn('is_embedded');
        });
    }
};
