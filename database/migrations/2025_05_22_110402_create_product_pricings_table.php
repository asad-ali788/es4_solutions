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
        Schema::create('product_pricings', function (Blueprint $table) {
            $table->id();
            $table->decimal('item_price', 10, 2)->nullable();
            $table->decimal('postage', 10, 2)->nullable();
            $table->decimal('base_price', 10, 2)->nullable();
            $table->decimal('fba_fee', 10, 2)->nullable();
            $table->decimal('duty', 10, 2)->nullable();
            $table->decimal('air_ship', 10, 2)->nullable();
            // Foreign key referencing product_listings table
            $table->foreignId('product_listings_id')->constrained('product_listings')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_pricings');
    }
};
