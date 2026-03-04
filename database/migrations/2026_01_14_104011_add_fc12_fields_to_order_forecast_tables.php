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
            $table->unsignedInteger('ai_fc12_total')->nullable()->after('last12_total_sold');
            $table->unsignedInteger('system_fc12_total')->nullable()->after('ai_fc12_total');
            $table->char('forecast_month', 7)->nullable()->after('system_fc12_total');
        });

        // order_forecast_snapshot_asins
        Schema::table('order_forecast_snapshot_asins', function (Blueprint $table) {
            $table->unsignedInteger('ai_fc12_total')->nullable()->after('last12_total_sold');
            $table->unsignedInteger('system_fc12_total')->nullable()->after('ai_fc12_total');
            $table->char('forecast_month', 7)->nullable()->after('system_fc12_total');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_forecast_snapshots', function (Blueprint $table) {
            $table->dropColumn([
                'ai_fc12_total',
                'system_fc12_total',
                'forecast_month',
            ]);
        });

        Schema::table('order_forecast_snapshot_asins', function (Blueprint $table) {
            $table->dropColumn([
                'ai_fc12_total',
                'system_fc12_total',
                'forecast_month',
            ]);
        });
    }
};
