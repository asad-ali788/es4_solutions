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
        Schema::table('temp_amz_ads_keyword_performance_report', function (Blueprint $table) {
            $table->unique(['campaign_id', 'keyword_id', 'c_date', 'country'], 'temp_amz_ads_keyword_perf_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('temp_amz_ads_keyword_performance_report', function (Blueprint $table) {
            $table->dropUnique('temp_amz_ads_keyword_perf_unique');
        });
    }
};
