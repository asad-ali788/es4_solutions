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
        Schema::create('afn_inventory_data', function (Blueprint $table) {
            $table->id();
            $table->string('seller_sku')->nullable();
            $table->string('fulfillment_channel_sku')->nullable();
            $table->string('asin')->nullable();
            $table->string('condition_type')->nullable();
            $table->string('warehouse_condition_code')->nullable();
            $table->string('quantity_available')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('afn_inventory_data');
    }
};
