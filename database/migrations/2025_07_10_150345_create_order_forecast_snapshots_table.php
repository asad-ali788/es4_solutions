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
        Schema::create('order_forecast_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_forecast_id')->constrained('order_forecasts')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->string('product_sku', 255)->nullable();
            $table->text('product_title')->nullable();
            $table->json('product_price')->nullable();
            $table->string('country', 50)->nullable();
            $table->integer('amazon_stock')->nullable();
            $table->integer('warehouse_stock')->nullable();
            $table->json('routes')->nullable();
            $table->json('shipment_in_transit')->nullable();
            $table->integer('ytd_sales')->nullable();
            $table->json('sales_by_month_last_3_months')->nullable();
            $table->json('sales_by_month_last_12_months')->nullable();
            $table->json('input_data_by_month_12_months')->nullable();
            $table->string('product_img', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_forecast_snapshots');
    }
};
