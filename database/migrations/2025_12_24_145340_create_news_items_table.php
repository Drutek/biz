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
        Schema::create('news_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tracked_entity_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('snippet');
            $table->string('url');
            $table->string('source');
            $table->datetime('published_at')->nullable();
            $table->datetime('fetched_at');
            $table->boolean('is_read')->default(false);
            $table->boolean('is_relevant')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('news_items');
    }
};
