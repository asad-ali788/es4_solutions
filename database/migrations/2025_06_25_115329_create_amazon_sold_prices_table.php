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
        Schema::create('amazon_sold_price', function (Blueprint $table) {
            $table->id();
            $table->string('asin')->index();
            $table->string('marketplace_id');
            $table->string('seller_sku')->nullable();
            $table->string('offer_type')->nullable();

            // Pricing
            $table->decimal('listing_price', 10, 2)->nullable();
            $table->decimal('landed_price', 10, 2)->nullable();
            $table->decimal('shipping_price', 10, 2)->nullable();
            $table->decimal('regular_price', 10, 2)->nullable();
            $table->decimal('business_price', 10, 2)->nullable();

            // Points
            $table->integer('points_number')->nullable();
            $table->decimal('points_value', 10, 2)->nullable();

            // Conditions
            $table->string('item_condition')->nullable();
            $table->string('item_sub_condition')->nullable();
            $table->string('fulfillment_channel')->nullable();

            // JSON fields
            $table->json('sales_rankings')->nullable();
            $table->json('quantity_discount_prices')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('amazon_sold_prices');
    }
};
