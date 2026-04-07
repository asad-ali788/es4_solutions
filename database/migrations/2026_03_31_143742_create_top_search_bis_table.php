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
        Schema::create('top_search_bis', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('search_frequency_rank')->default(0);
            $table->string('search_term');
            $table->string('top_clicked_brand_1')->nullable();
            $table->string('top_clicked_brand_2')->nullable();
            $table->string('top_clicked_brand_3')->nullable();
            $table->string('top_clicked_category_1')->nullable();
            $table->string('top_clicked_category_2')->nullable();
            $table->string('top_clicked_category_3')->nullable();
            $table->string('top_clicked_product_1_asin')->nullable();
            $table->string('top_clicked_product_2_asin')->nullable();
            $table->string('top_clicked_product_3_asin')->nullable();
            $table->unsignedInteger('week')->nullable();
            $table->dateTime('reporting_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Unique key for upserting. Search term + reporting date combinations are usually unique for search terms at a given time
            $table->unique(['search_term', 'reporting_date'], 'search_term_report_date_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('top_search_bis');
    }
};
