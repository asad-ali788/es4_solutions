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
        Schema::create('campaign_budget_recommendations', function (Blueprint $table) {
            $table->id();

            // Campaign context
            $table->string('campaign_id', 50)->index();
            $table->string('campaign_type', 10)->index(); // SP / SB
            $table->string('rule_id', 50)->index();
            $table->string('rule_name')->nullable();

            // Recommendation values
            $table->integer('suggested_budget')->nullable();
            $table->unsignedSmallInteger('suggested_budget_increase_percent')->default(0);

            // 7-day missed opportunity window
            $table->date('seven_days_start_date')->nullable();
            $table->date('seven_days_end_date')->nullable();

            // Missed opportunity metrics
            $table->decimal('estimated_missed_sales_lower', 12, 2)->nullable();
            $table->decimal('estimated_missed_sales_upper', 12, 2)->nullable();
            $table->decimal('percent_time_in_budget', 5, 2)->nullable();

            // Laravel defaults
            $table->timestamps();
            $table->softDeletes();

            // Prevent duplicate recommendations per rule per campaign
            $table->unique(
                ['campaign_id', 'campaign_type', 'rule_id'],
                'uniq_campaign_rule_recommendation'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaign_budget_recommendations');
    }
};
