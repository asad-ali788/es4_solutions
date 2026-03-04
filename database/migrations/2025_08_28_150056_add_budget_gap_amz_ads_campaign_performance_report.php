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
        Schema::table('amz_ads_campaign_performance_report', function (Blueprint $table) {
           $table->boolean('budget_gap')->after('c_budget')->default(0);
        });
        Schema::table('amz_ads_campaign_performance_reports_sb', function (Blueprint $table) {
           $table->boolean('budget_gap')->after('c_budget')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
