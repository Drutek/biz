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
        Schema::create('user_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('standup_email_enabled')->default(true);
            $table->string('standup_email_time')->default('08:00');
            $table->string('standup_email_timezone')->default('UTC');
            $table->boolean('in_app_notifications_enabled')->default(true);
            $table->boolean('proactive_insights_enabled')->default(true);
            $table->unsignedInteger('runway_alert_threshold')->default(3);
            $table->timestamps();

            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_preferences');
    }
};
