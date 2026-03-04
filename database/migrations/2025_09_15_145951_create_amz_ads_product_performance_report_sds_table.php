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
        Schema::create('amz_ads_product_performance_report_sd', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('campaign_id')->nullable();
            $table->bigInteger('ad_group_id')->nullable();
            $table->bigInteger('ad_id')->nullable();

            $table->string('sku')->nullable();
            $table->string('asin')->nullable();

            $table->integer('clicks')->nullable();
            $table->bigInteger('impressions')->nullable();
            $table->decimal('cost', 12, 4)->nullable();

            $table->decimal('sales', 12, 4)->nullable();
            $table->integer('purchases')->nullable();
            $table->integer('units_sold')->nullable();

            $table->date('date')->nullable();
            $table->string('country')->nullable();
            $table->dateTime('added')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('amz_ads_product_performance_report_sd');
    }
};
