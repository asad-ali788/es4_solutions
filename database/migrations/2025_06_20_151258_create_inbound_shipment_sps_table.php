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
        Schema::create('inbound_shipment_sps', function (Blueprint $table) {
            $table->id();
            $table->string('shipment_id', 255);
            $table->string('ship_status', 50)->nullable();
            $table->date('add_date');
            $table->date('ship_arrival_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inbound_shipment_sps');
    }
};
