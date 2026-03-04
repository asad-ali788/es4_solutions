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
        Schema::create('historical_forecast_metric_asins', function (Blueprint $table) {
            $table->id();
            $table->string('product_asin')->nullable();
            $table->json('metrics')->nullable();
            $table->string('metrics_key')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historical_forecast_metric_asins');
    }
};
