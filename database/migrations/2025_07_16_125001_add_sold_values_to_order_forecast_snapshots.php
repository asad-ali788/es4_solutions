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
        Schema::table('order_forecast_snapshots', function (Blueprint $table) {
            $table->json('sold_values_by_month')->nullable()->after('input_data_by_month_12_months');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_forecast_snapshots', function (Blueprint $table) {
            //
        });
    }
};
