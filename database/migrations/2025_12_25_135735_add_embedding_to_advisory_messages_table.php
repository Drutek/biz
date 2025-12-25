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

        Schema::table('advisory_messages', function (Blueprint $table) {
            $table->boolean('is_embedded')->default(false)->index();
        });

        DB::statement('ALTER TABLE advisory_messages ADD COLUMN embedding vector(1536)');
        DB::statement('CREATE INDEX advisory_messages_embedding_idx ON advisory_messages USING hnsw (embedding vector_cosine_ops)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP INDEX IF EXISTS advisory_messages_embedding_idx');
        DB::statement('ALTER TABLE advisory_messages DROP COLUMN IF EXISTS embedding');

        Schema::table('advisory_messages', function (Blueprint $table) {
            $table->dropColumn('is_embedded');
        });
    }
};
