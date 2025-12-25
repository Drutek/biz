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

        Schema::table('proactive_insights', function (Blueprint $table) {
            $table->boolean('is_embedded')->default(false)->index();
        });

        DB::statement('ALTER TABLE proactive_insights ADD COLUMN embedding vector(1536)');
        DB::statement('CREATE INDEX proactive_insights_embedding_idx ON proactive_insights USING hnsw (embedding vector_cosine_ops)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP INDEX IF EXISTS proactive_insights_embedding_idx');
        DB::statement('ALTER TABLE proactive_insights DROP COLUMN IF EXISTS embedding');

        Schema::table('proactive_insights', function (Blueprint $table) {
            $table->dropColumn('is_embedded');
        });
    }
};
