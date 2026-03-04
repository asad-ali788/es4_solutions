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
        Schema::create('amz_ads_sb_purchased_product_reports', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('campaign_id')->nullable();
            $table->string('campaign_name')->nullable();
            $table->bigInteger('ad_group_id')->nullable();
            $table->string('ad_group_name')->nullable();
            $table->string('asin')->nullable();
            $table->string('product_name')->nullable();
            $table->string('product_cat')->nullable();
            $table->bigInteger('orders14d')->nullable();
            $table->decimal('sales14d', 10, 2)->nullable();
            $table->bigInteger('units_sold14d')->nullable();
            $table->bigInteger('ntb_orders14d')->nullable();
            $table->decimal('ntb_orders_pct14d', 10, 2)->nullable();
            $table->bigInteger('ntb_purchases14d')->nullable();
            $table->decimal('ntb_purchases_pct14d', 10, 2)->nullable();
            $table->decimal('ntb_sales14d', 10, 2)->nullable();
            $table->decimal('ntb_sales_pct14d', 10, 2)->nullable();
            $table->bigInteger('ntb_units14d')->nullable();
            $table->decimal('ntb_units_pct14d', 10, 2)->nullable();
            $table->date('c_date')->nullable();
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
        Schema::dropIfExists('amz_ads_sb_purchased_product_reports');
    }
};
