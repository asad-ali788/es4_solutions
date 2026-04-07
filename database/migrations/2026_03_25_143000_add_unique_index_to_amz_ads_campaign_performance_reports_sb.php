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
        Schema::table('amz_ads_campaign_performance_reports_sb', function (Blueprint $table) {
            $table->unique(['campaign_id', 'date', 'country'], 'amz_ads_campaign_sb_perf_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('amz_ads_campaign_performance_reports_sb', function (Blueprint $table) {
            $table->dropUnique('amz_ads_campaign_sb_perf_unique');
        });
    }
};
