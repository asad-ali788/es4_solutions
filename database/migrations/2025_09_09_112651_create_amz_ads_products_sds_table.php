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
        Schema::create('amz_ads_products_sd', function (Blueprint $table) {
            $table->id();
            $table->string('country', 2)->nullable();
            $table->bigInteger('ad_id')->nullable();
            $table->string('state')->nullable();
            $table->bigInteger('ad_group_id')->nullable();
            $table->bigInteger('campaign_id')->nullable();
            $table->string('ad_name')->nullable();
            $table->string('asin')->nullable();
            $table->string('sku')->nullable();
            $table->string('landing_page_url')->nullable();
            $table->string('landing_page_type')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('amz_ads_products_sd');
    }
};
