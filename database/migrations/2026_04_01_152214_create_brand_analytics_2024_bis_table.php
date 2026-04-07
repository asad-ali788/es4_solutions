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
        Schema::create('brand_analytics_2024_bis', function (Blueprint $table) {
            $table->id();
            $table->string('asin')->index();
            $table->string('name')->nullable();
            $table->string('search_query')->index();
            $table->integer('search_query_score')->nullable();
            $table->integer('search_query_volume')->nullable();
            $table->date('reporting_date')->index();
            $table->string('week')->nullable();
            $table->integer('year')->nullable();
            $table->integer('impressions_total_count')->nullable();
            $table->integer('impressions_asin_count')->nullable();
            $table->integer('clicks_total_count')->nullable();
            $table->integer('clicks_asin_count')->nullable();
            $table->decimal('clicks_price_median', 15, 2)->nullable();
            $table->decimal('clicks_asin_price_median', 15, 2)->nullable();
            $table->integer('clicks_shipping_same_day')->nullable();
            $table->integer('clicks_shipping_1d')->nullable();
            $table->integer('clicks_shipping_2d')->nullable();
            $table->integer('cart_adds_total_count')->nullable();
            $table->integer('cart_adds_asin_count')->nullable();
            $table->integer('purchases_total_count')->nullable();
            $table->integer('purchases_asin_count')->nullable();
            $table->decimal('impressions_asin_share_pct', 8, 4)->nullable();
            $table->decimal('clicks_rate_pct', 8, 4)->nullable();
            $table->decimal('clicks_asin_share_pct', 8, 4)->nullable();
            $table->decimal('purchases_rate_pct', 8, 4)->nullable();
            $table->decimal('purchases_asin_share_pct', 8, 4)->nullable();
            $table->timestamps();

            $table->unique(['asin', 'search_query', 'reporting_date'], 'ba_2024_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('brand_analytics_2024_bis');
    }
};
