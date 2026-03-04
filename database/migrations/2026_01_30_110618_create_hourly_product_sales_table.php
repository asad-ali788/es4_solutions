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
        Schema::create('hourly_product_sales', function (Blueprint $table) {
            $table->id();
            $table->string('sku');
            $table->string('asin')->nullable();
            $table->string('sales_channel', 20);
            $table->timestamp('purchase_date')->nullable();
            $table->timestamp('sale_hour')->nullable();
            $table->integer('total_units')->default(0);
            $table->decimal('item_price', 10, 2)->default(0.00);
            $table->string('currency', 10)->default('USD');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(
                ['sku', 'sales_channel', 'sale_hour'],
                'uniq_hourly_product_sales'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hourly_product_sales');
    }
};
