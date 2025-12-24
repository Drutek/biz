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
        Schema::create('proactive_insights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('trigger_type');
            $table->json('trigger_context')->nullable();
            $table->string('insight_type');
            $table->string('title');
            $table->text('content');
            $table->string('priority')->default('medium');
            $table->boolean('is_read')->default(false);
            $table->boolean('is_dismissed')->default(false);
            $table->foreignId('related_event_id')->nullable()->constrained('business_events')->nullOnDelete();
            $table->string('provider')->nullable();
            $table->string('model')->nullable();
            $table->unsignedInteger('tokens_used')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'is_read', 'is_dismissed']);
            $table->index(['user_id', 'priority']);
            $table->index(['user_id', 'insight_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proactive_insights');
    }
};
