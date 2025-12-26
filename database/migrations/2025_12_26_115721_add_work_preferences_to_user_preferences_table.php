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
        Schema::table('user_preferences', function (Blueprint $table) {
            $table->boolean('weekends_are_workdays')->default(false);
            $table->boolean('task_suggestions_enabled')->default(true);
            $table->boolean('overdue_reminders_enabled')->default(true);
            $table->string('overdue_reminder_time')->default('09:00');
            $table->boolean('interactive_standup_enabled')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_preferences', function (Blueprint $table) {
            $table->dropColumn([
                'weekends_are_workdays',
                'task_suggestions_enabled',
                'overdue_reminders_enabled',
                'overdue_reminder_time',
                'interactive_standup_enabled',
            ]);
        });
    }
};
