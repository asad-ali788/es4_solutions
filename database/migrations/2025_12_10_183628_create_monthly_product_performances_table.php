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
        Schema::create('monthly_ads_product_performances', function (Blueprint $table) {

            $table->id();
            $table->string('sku');
            $table->string('asin');
            $table->date('month');
            $table->unsignedBigInteger('sold')->default(0);
            $table->decimal('revenue', 12, 2)->default(0);
            $table->decimal('ad_spend', 12, 2)->default(0);
            $table->decimal('ad_sales', 12, 2)->default(0);
            $table->decimal('acos', 8, 2)->default(0);
            $table->decimal('tacos', 8, 2)->default(0);
            $table->timestamps();
            $table->unique(['sku', 'asin', 'month']);
            $table->index(['sku', 'asin']);
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monthly_ads_product_performances');
    }
};
