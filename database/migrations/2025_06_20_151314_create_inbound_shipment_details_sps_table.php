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
        Schema::create('inbound_shipment_details_sps', function (Blueprint $table) {
            $table->id();
            $table->string('sku', 255);
            $table->integer('qty_ship');
            $table->integer('qty_received');
            $table->string('ship_id', 255); // Foreign key to inbound_shipment_sps.shipment_id
            $table->date('add_date');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inbound_shipment_details_sps');
    }
};
