<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('brand_analytics_weekly_data_bi', function (Blueprint $table) {
            $table->id();
            $table->string('asin', 50)->nullable()->index();
            
            // Dimensions
            $table->string('week_number', 50)->nullable()->index();
            $table->string('week_date')->nullable()->index();
            $table->string('week_year', 10)->nullable()->index();

            // Metrics
            $table->unsignedBigInteger('impressions')->nullable();
            $table->unsignedInteger('clicks')->nullable();
            $table->unsignedInteger('orders')->nullable();
   
            $table->timestamps();
            // Unique index for upsert
            $table->unique(['asin', 'week_date', 'week_year'], 'brand_analytics_weekly_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('brand_analytics_weekly_data_bi');
    }
};
