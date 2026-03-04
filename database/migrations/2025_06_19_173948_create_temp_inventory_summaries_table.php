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
        Schema::create('temp_inventory_summaries', function (Blueprint $table) {
            $table->id();
            $table->string('asin')->nullable();
            $table->string('fn_sku')->nullable();
            $table->string('seller_sku')->nullable();
            $table->string('condition')->nullable();
            $table->timestamp('last_updated_time')->nullable();
            $table->string('product_name')->nullable();
            $table->integer('total_quantity')->default(0);
            $table->integer('fulfillableQuantity')->default(0);
            $table->integer('inboundWorkingQuantity')->default(0);
            $table->integer('inboundShippedQuantity')->default(0);
            $table->integer('inboundReceivingQuantity')->default(0);
            $table->integer('totalReservedQuantity')->default(0);
            $table->json('inventoryDetails')->nullable();
            $table->json('stores')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('temp_inventory_summaries');
    }
};
