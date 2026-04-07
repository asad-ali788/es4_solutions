<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('advertised_product_bi', function (Blueprint $table) {
            $table->id();

            // Dimensions
            $table->date('report_date')->index();
            $table->string('campaign_name', 255)->nullable()->index();
            $table->string('asin', 20)->nullable()->index(); // ASIN standard length
            $table->string('currency', 10)->nullable();

            // Metrics
            $table->unsignedInteger('units')->nullable();
            $table->decimal('sales', 12, 2)->nullable();

            $table->timestamps();

            // Composite unique index for upsert
            $table->unique(['report_date', 'campaign_name', 'asin'], 'advertised_product_bi_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('advertised_product_bi');
    }
};
