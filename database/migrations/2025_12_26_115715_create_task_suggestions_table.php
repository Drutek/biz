<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('task_suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('proactive_insight_id')->constrained()->cascadeOnDelete();
            $table->string('suggestion_hash');
            $table->boolean('was_accepted')->default(false);
            $table->boolean('was_rejected')->default(false);
            $table->timestamp('suggested_at')->useCurrent();
            $table->timestamps();

            $table->unique(['user_id', 'suggestion_hash']);
            $table->index(['user_id', 'proactive_insight_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_suggestions');
    }
};
