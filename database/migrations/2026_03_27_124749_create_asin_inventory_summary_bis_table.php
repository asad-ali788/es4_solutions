<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('asin_inventory_summary_bi', function (Blueprint $table) {
            $table->id();
            $table->string('asin')->unique();

            $table->integer('fba_available')->default(0);
            $table->integer('fba_inbound_working')->default(0);
            $table->integer('fba_inbound_shipped')->default(0);
            $table->integer('fba_inbound_receiving')->default(0);
            $table->integer('fc_reserved')->default(0);

            $table->integer('awd_available')->default(0);
            $table->integer('awd_inbound')->default(0);

            $table->integer('apa_warehouse_available')->default(0);

            $table->integer('flex_warehouse_available')->default(0);
            $table->integer('shipout_warehouse_inventory')->default(0);
            $table->integer('tactical_warehouse_inventory')->default(0);
            // =========================
            // META
            // =========================
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
            // Index for faster lookups
            $table->index('asin');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asin_inventory_summary_bi');
    }
};
