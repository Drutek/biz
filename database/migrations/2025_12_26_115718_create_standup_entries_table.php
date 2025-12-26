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
        Schema::create('standup_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('daily_standup_id')->constrained()->cascadeOnDelete();
            $table->text('yesterday_accomplished')->nullable();
            $table->text('today_planned')->nullable();
            $table->text('blockers')->nullable();
            $table->json('ai_follow_up_questions')->nullable();
            $table->json('ai_follow_up_responses')->nullable();
            $table->text('ai_analysis')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'daily_standup_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('standup_entries');
    }
};
