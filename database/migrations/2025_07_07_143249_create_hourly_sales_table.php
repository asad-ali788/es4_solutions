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
        Schema::create('hourly_sales', function (Blueprint $table) {
            $table->id();
            $table->string('sku', 50);
            $table->foreignId('product_listings_id')->nullable();
            $table->string('marketplace_id', 20);
            $table->timestamp('sale_date')->nullable();
            $table->integer('total_units')->default(0);
            $table->decimal('total_revenue', 10, 2)->default(0.00);
            $table->decimal('total_cost', 10, 2)->default(0.00);
            $table->decimal('total_profit', 10, 2)->default(0.00);
            $table->string('currency', 10)->default('USD');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hourly_sales');
    }
};
