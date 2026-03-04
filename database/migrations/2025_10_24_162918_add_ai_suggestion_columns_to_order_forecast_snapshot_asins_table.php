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
        Schema::table('order_forecast_snapshot_asins', function (Blueprint $table) {
            $table->json('ai_recommendation_data_by_month_12_months')->nullable()->after('input_data_by_month_12_months');
            $table->boolean('run_update')->default(false)->after('order_amount');
            $table->enum('run_status', ['pending', 'dispatched', 'failed', 'running', 'done'])->default('pending')->after('run_update');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_forecast_snapshot_asins', function (Blueprint $table) {
            $table->dropColumn(['ai_recommendation_data_by_month_12_months', 'run_update', 'run_status']);
        });
    }
};
