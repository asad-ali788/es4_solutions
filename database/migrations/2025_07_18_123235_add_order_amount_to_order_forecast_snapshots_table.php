<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('order_forecast_snapshots', function (Blueprint $table) {
            $table->integer('order_amount')->nullable()->after('sold_values_by_month');
        });
    }

    public function down()
    {
        Schema::table('order_forecast_snapshots', function (Blueprint $table) {
            $table->dropColumn('order_amount');
        });
    }
};
