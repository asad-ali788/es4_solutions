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
        Schema::create('product_forecast_asins', function (Blueprint $table) {
            $table->id();
            $table->string('product_asin')->nullable();
            $table->date('forecast_month'); // Store like '2025-08-01'
            $table->enum('forecast_half', ['H1', 'H2'])->nullable();
            $table->integer('forecast_units')->default(0);
            $table->integer('actual_units_sold')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_forecast_asins');
    }
};
