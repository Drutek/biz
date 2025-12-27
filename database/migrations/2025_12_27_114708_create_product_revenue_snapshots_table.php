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
        Schema::create('product_revenue_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->decimal('mrr', 10, 2)->default(0);
            $table->decimal('total_revenue', 12, 2)->default(0);
            $table->integer('subscriber_count')->default(0);
            $table->integer('units_sold')->default(0);
            $table->date('recorded_at');
            $table->timestamps();

            $table->unique(['product_id', 'recorded_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_revenue_snapshots');
    }
};
