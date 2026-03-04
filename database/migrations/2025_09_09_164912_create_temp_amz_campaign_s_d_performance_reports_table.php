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
        Schema::create('temp_amz_campaign_sd_performance_report', function (Blueprint $table) {
            $table->id();
            $table->string('campaign_id')->nullable();
            $table->string('campaign_status')->nullable();
            $table->decimal('campaign_budget_amount', 15, 2)->nullable();
            $table->string('campaign_budget_currency_code', 10)->nullable();
            $table->bigInteger('impressions')->nullable();
            $table->bigInteger('clicks')->nullable();
            $table->decimal('cost', 15, 2)->nullable();
            $table->decimal('sales', 15, 2)->nullable();
            $table->bigInteger('purchases')->nullable();
            $table->bigInteger('units_sold')->nullable();
            $table->date('c_date')->nullable();
            $table->string('country', 10)->nullable();
            $table->date('added')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('temp_amz_campaign_sd_performance_report');
    }
};
