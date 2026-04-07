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
        Schema::create('daily_ads_product_performances', function (Blueprint $table) {
            $table->id();
            $table->string('sku');
            $table->string('asin');
            $table->date('sale_date');
            $table->unsignedBigInteger('sold')->nullable();
            $table->decimal('revenue', 12, 2)->nullable();
            $table->decimal('ad_spend', 12, 2)->nullable();
            $table->decimal('ad_sales', 12, 2)->nullable();
            $table->unsignedInteger('ad_units')->nullable()->comment('purchase values');
            $table->decimal('acos', 8, 2)->nullable();
            $table->decimal('tacos', 8, 2)->nullable();
            $table->unique(['sku', 'asin', 'sale_date']);
            $table->index(['sku', 'asin']);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_ads_product_performances');
    }
};
