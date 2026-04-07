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
        // 1. Sponsored Display (SD) Products
        Schema::table('amz_ads_product_performance_report_sd', function (Blueprint $table) {
            $table->unique(['campaign_id', 'ad_group_id', 'ad_id', 'sku', 'date', 'country'], 'sd_product_perf_unique');
        });

        // 2. SB Purchased Products
        Schema::table('amz_ads_sb_purchased_product_reports', function (Blueprint $table) {
            $table->unique(['campaign_id', 'ad_group_id', 'asin', 'c_date', 'country'], 'purchased_product_perf_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('amz_ads_product_performance_report_sd', function (Blueprint $table) {
            $table->dropUnique('sd_product_perf_unique');
        });

        Schema::table('amz_ads_sb_purchased_product_reports', function (Blueprint $table) {
            $table->dropUnique('purchased_product_perf_unique');
        });
    }
};
