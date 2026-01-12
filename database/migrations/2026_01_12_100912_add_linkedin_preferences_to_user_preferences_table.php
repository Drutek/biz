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
            $table->boolean('linkedin_posts_enabled')->default(true);
            $table->string('linkedin_post_frequency')->default('weekly');
            $table->unsignedTinyInteger('linkedin_posts_per_generation')->default(3);
            $table->string('linkedin_default_tone')->default('professional');
            $table->json('linkedin_topics')->nullable();
            $table->boolean('linkedin_include_hashtags')->default(true);
            $table->boolean('linkedin_include_cta')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_preferences', function (Blueprint $table) {
            $table->dropColumn([
                'linkedin_posts_enabled',
                'linkedin_post_frequency',
                'linkedin_posts_per_generation',
                'linkedin_default_tone',
                'linkedin_topics',
                'linkedin_include_hashtags',
                'linkedin_include_cta',
            ]);
        });
    }
};
