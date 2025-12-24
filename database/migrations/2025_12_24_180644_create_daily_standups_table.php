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
        Schema::create('daily_standups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('standup_date');
            $table->json('financial_snapshot');
            $table->json('alerts');
            $table->text('ai_summary')->nullable();
            $table->json('ai_insights')->nullable();
            $table->unsignedInteger('events_count')->default(0);
            $table->timestamp('generated_at');
            $table->timestamp('email_sent_at')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'standup_date']);
            $table->index(['user_id', 'standup_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_standups');
    }
};
