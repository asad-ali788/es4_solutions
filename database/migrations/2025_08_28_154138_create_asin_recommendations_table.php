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
        Schema::create('asin_recommendations', function (Blueprint $table) {
            $table->id();
            $table->string('asin', 20);
            $table->date('report_week');
            $table->integer('active_campaigns')->default(0);
            $table->integer('enabled_campaigns_count')->default(0);
            $table->string('country')->nullable();
            $table->decimal('total_daily_budget', 12, 2)->nullable();
            $table->decimal('total_spend', 12, 2)->nullable();
            $table->decimal('total_sales', 12, 2)->nullable();
            $table->decimal('acos', 6, 2)->nullable();
            $table->string('campaign_types', 255)->nullable();
            $table->string('recommendation', 255)->nullable();
            $table->string('rule_applied', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['asin', 'report_week']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asin_recommendations');
    }
};
