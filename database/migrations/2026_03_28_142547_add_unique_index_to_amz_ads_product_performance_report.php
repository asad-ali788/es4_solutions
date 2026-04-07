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
        Schema::table('amz_ads_product_performance_report', function (Blueprint $table) {
            $table->unique(['campaign_id', 'ad_group_id', 'ad_id', 'sku', 'c_date', 'country'], 'amz_ads_product_perf_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('amz_ads_product_performance_report', function (Blueprint $table) {
            $table->dropUnique('amz_ads_product_perf_unique');
        });
    }
};
