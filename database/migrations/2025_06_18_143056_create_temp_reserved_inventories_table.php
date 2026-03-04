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
        Schema::create('temp_reserved_inventories', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->nullable();
            $table->string('fnsku')->nullable();
            $table->string('asin')->nullable();
            $table->text('product_name')->nullable();
            $table->string('reserved_qty')->nullable();
            $table->string('reserved_customerorders')->nullable();
            $table->string('reserved_fc_transfers')->nullable();
            $table->string('reserved_fc_processing')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('temp_reserved_inventories');
    }
};
