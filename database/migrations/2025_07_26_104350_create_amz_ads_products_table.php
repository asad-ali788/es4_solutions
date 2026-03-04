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
        Schema::create('amz_ads_products', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('ad_group_id')->unique();
            $table->bigInteger('campaign_id')->nullable();
            $table->string('country')->nullable();
            $table->string('ad_id')->nullable();
            $table->string('asin', 50)->nullable();
            $table->string('sku')->nullable();
            $table->string('state', 50)->nullable();
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
        Schema::dropIfExists('amz_ads_products');
    }
};
