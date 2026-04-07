<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('search_term_report_bi', function (Blueprint $table) {
            $table->id();

            // Dimensions
            $table->date('report_date')->index();

            $table->string('campaign_name', 255)->nullable()->index();
            $table->string('portfolio_name', 255)->nullable();

            $table->string('targeting', 150)->nullable();
            $table->string('match_type', 100)->nullable()->index();
            $table->string('customer_search_term', 255)->nullable()->index();

            // Metrics
            $table->unsignedBigInteger('impressions')->nullable();
            $table->unsignedInteger('clicks')->nullable();

            $table->decimal('spend', 12, 2)->nullable();
            $table->decimal('sales', 12, 2)->nullable();

            $table->unsignedInteger('units')->nullable();

            $table->timestamps();

            // Composite unique index for upsert
            $table->unique(['report_date', 'campaign_name', 'targeting'], 'search_term_report_bi_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_term_report_bi');
    }
};
