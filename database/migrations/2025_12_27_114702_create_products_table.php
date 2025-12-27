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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('product_type');
            $table->string('status')->default('idea');
            $table->decimal('price', 10, 2)->nullable();
            $table->string('pricing_model')->default('one_time');
            $table->string('billing_frequency')->nullable();

            // Revenue metrics (user-updated)
            $table->decimal('mrr', 10, 2)->default(0);
            $table->decimal('total_revenue', 12, 2)->default(0);
            $table->integer('subscriber_count')->default(0);
            $table->integer('units_sold')->default(0);

            // Time investment
            $table->decimal('hours_invested', 8, 2)->default(0);
            $table->decimal('monthly_maintenance_hours', 6, 2)->default(0);

            // Dates & metadata
            $table->date('launched_at')->nullable();
            $table->date('target_launch_date')->nullable();
            $table->string('url')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
