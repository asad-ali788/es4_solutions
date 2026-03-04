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
        Schema::create('ship_out_warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('warehouse_sku')->nullable()->comment('omsSku');
            $table->string('amazon_sku')->nullable()->comment('skuNameEN');
            $table->string('asin')->nullable();
            $table->foreignId('warehouse_id')->constrained('warehouses')->onDelete('cascade');
            $table->tinyInteger('status')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ship_out_warehouses');
    }
};
