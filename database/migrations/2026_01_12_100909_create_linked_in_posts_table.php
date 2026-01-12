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
        Schema::create('linked_in_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('news_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('post_type');
            $table->string('tone');
            $table->string('title');
            $table->text('content');
            $table->json('hashtags')->nullable();
            $table->string('call_to_action')->nullable();
            $table->boolean('is_used')->default(false);
            $table->boolean('is_dismissed')->default(false);
            $table->string('provider')->nullable();
            $table->string('model')->nullable();
            $table->unsignedInteger('tokens_used')->nullable();
            $table->timestamp('generated_at');
            $table->timestamps();

            $table->index(['user_id', 'is_dismissed', 'is_used']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('linked_in_posts');
    }
};
