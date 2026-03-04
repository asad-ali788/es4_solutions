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
        Schema::create('fba_inventory_usa', function (Blueprint $table) {
            $table->id();
            $table->string('sku');
            $table->integer('instock')->default(0);
            $table->integer('totalstock')->default(0);
            $table->integer('reserve_stock')->default(0);
            $table->string('country')->nullable();
            $table->date('add_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fba_inventory_usa');
    }
};
