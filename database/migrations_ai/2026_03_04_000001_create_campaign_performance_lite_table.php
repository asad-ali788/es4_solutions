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
        Schema::create('campaign_performance_lite', function (Blueprint $table) {
            $table->id();

            // Campaign identifiers
            $table->string('campaign_id')->index();
            $table->string('campaign_name');
            $table->date('report_date')->index();

            // Campaign classification (unified state replaces sp_state, sb_state, sd_state)
            $table->string('campaign_types', 10)->index();  // SP, SB, SD
            $table->string('campaign_state', 20)->nullable()->index();  // ENABLED, PAUSED, ARCHIVED
            $table->string('sp_targeting_type', 20)->nullable()->index();  // AUTO, MANUAL (SP only)

            // Geographic/Market
            $table->string('country', 5)->index();

            // Product fields
            $table->string('asin', 20)->nullable()->index();

            // Budget fields
            $table->decimal('total_daily_budget', 10, 2)->default(0);

            // Daily metrics
            $table->decimal('total_spend', 10, 2)->default(0);
            $table->decimal('total_sales', 10, 2)->default(0);
            $table->integer('purchases7d')->default(0);
            $table->decimal('acos', 10, 2)->default(0);

            // Composite indexes for common queries
            $table->unique(['campaign_id', 'report_date', 'campaign_types', 'country', 'asin'], 'cpl_unique_campaign_date_type_country_asin');
            $table->index(['report_date', 'campaign_types']);
            $table->index(['report_date', 'country']);
            $table->index(['report_date', 'campaign_state']);
            $table->index(['asin', 'report_date']);
            $table->index(['campaign_state', 'campaign_types']);  // For campaign_state + campaign_types filtering
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaign_performance_lite');
    }
};
