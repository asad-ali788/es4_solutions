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
        Schema::create('campaign_recommendations', function (Blueprint $table) {
            $table->id();
            $table->string('campaign_id');
            $table->string('campaign_name')->nullable();
            $table->date('report_week');
            $table->string('campaign_types', 255)->nullable();
            $table->integer('enabled_campaigns_count')->nullable();
            $table->string('country')->nullable();
            $table->decimal('total_daily_budget', 12, 2)->nullable();

            $table->decimal('total_spend', 12, 2)->nullable();
            $table->decimal('total_sales', 12, 2)->nullable();
            $table->integer('purchases7d')->nullable();
            $table->decimal('acos', 6, 2)->nullable();

            $table->decimal('total_spend_7d', 12, 2)->nullable();
            $table->decimal('total_sales_7d', 12, 2)->nullable();
            $table->integer('purchases7d_7d')->nullable();
            $table->decimal('acos_7d', 6, 2)->nullable();

            $table->decimal('total_spend_14d', 12, 2)->nullable();
            $table->decimal('total_sales_14d', 12, 2)->nullable();
            $table->integer('purchases7d_14d')->nullable();

            $table->string('suggested_budget')->nullable();
            $table->string('ai_recommendation')->nullable();
            $table->string('ai_status')->nullable();
            $table->string('ai_suggested_budget')->nullable();
            $table->string('recommendation', 255)->nullable();
            $table->string('rule_applied', 255)->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaign_recommendations');
    }
};
