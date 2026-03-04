<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_forecast_metrics', function (Blueprint $table) {
            $table->tinyInteger('is_not_ready')->default(0)->after('metrics_by_month')->comment('1 = metrics generation in progress / not ready');
            $table->index('is_not_ready', 'idx_ofm_is_not_ready');
        });

        Schema::table('order_forecast_metric_asins', function (Blueprint $table) {
            $table->tinyInteger('is_not_ready')->default(0)->after('metrics_by_month')->comment('1 = metrics generation in progress / not ready');
            $table->index('is_not_ready', 'idx_ofma_is_not_ready');
        });
    }

    public function down(): void
    {
        Schema::table('order_forecast_metrics', function (Blueprint $table) {
            $table->dropIndex('idx_ofm_is_not_ready');
            $table->dropColumn('is_not_ready');
        });

        Schema::table('order_forecast_metric_asins', function (Blueprint $table) {
            $table->dropIndex('idx_ofma_is_not_ready');
            $table->dropColumn('is_not_ready');
        });
    }
};
