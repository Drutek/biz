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
        Schema::create('advisory_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('advisory_thread_id')->constrained()->cascadeOnDelete();
            $table->string('role');
            $table->text('content');
            $table->string('provider')->nullable();
            $table->string('model')->nullable();
            $table->integer('tokens_used')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('advisory_messages');
    }
};
