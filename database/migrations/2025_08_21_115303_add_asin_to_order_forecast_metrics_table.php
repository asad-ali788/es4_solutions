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
        Schema::table('order_forecast_metrics', function (Blueprint $table) {
            $table->string('asin1')->nullable()->after('product_sku');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_forecast_metrics', function (Blueprint $table) {
            $table->dropColumn('asin1');
        });
    }
};
