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
        Schema::create('temp_amz_ads_campaign_performance_reports_sb', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('campaign_id')->nullable();
            $table->integer('impressions')->nullable();
            $table->integer('clicks')->nullable();
            $table->integer('unitsSold')->nullable();
            $table->integer('purchases')->nullable();
            $table->decimal('cost', 10, 2)->nullable();
            $table->decimal('c_budget', 10, 2)->nullable();
            $table->string('c_currency')->nullable();
            $table->string('c_status')->nullable();
            $table->decimal('sales', 10, 2)->nullable();
            $table->dateTime('date')->nullable();
            $table->string('country')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('temp_amz_ads_campaign_performance_reports_sb');
    }
};
