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
        Schema::table('order_forecast_snapshot_asins', function (Blueprint $table) {
            $table->unsignedBigInteger('last12_total_sold')
                ->default(0)
                ->after('ytd_sales')
                ->index();
        });

        Schema::table('order_forecast_snapshots', function (Blueprint $table) {
            $table->unsignedBigInteger('last12_total_sold')
                ->default(0)
                ->after('ytd_sales')
                ->index();
        });
    }

    public function down()
    {
        Schema::table('order_forecast_snapshot_asins', function (Blueprint $table) {
            $table->dropColumn('last12_total_sold');
        });

        Schema::table('order_forecast_snapshots', function (Blueprint $table) {
            $table->dropColumn('last12_total_sold');
        });
    }
};
