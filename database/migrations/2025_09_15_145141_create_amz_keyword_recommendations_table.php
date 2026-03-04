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
        Schema::create('amz_keyword_recommendations', function (Blueprint $table) {
            $table->id();
            $table->string('keyword_id', 50);
            $table->string('campaign_id', 50);
            $table->string('keyword');
            $table->date('date');
            $table->integer('clicks')->default(0);
            $table->decimal('cpc')->default(0);
            $table->decimal('ctr')->default(0);
            $table->decimal('bid')->default(0);
            $table->string('country', 5)->nullable();
            $table->decimal('conversion_rate')->default(0);
            $table->string('campaign_types')->nullable();
            $table->string('impressions')->nullable();

            $table->decimal('total_spend', 12, 2)->nullable();
            $table->decimal('total_sales', 12, 2)->nullable();
            $table->integer('purchases1d')->nullable();
            $table->decimal('acos', 6, 2)->nullable();

            $table->decimal('total_spend_7d', 12, 2)->nullable();
            $table->decimal('total_sales_7d', 12, 2)->nullable();
            $table->integer('purchases1d_7d')->nullable();
            $table->decimal('acos_7d', 6, 2)->nullable();

            $table->decimal('total_spend_14d', 12, 2)->nullable();
            $table->decimal('total_sales_14d', 12, 2)->nullable();
            $table->integer('purchases1d_14d')->nullable();

            $table->string('recommendation')->nullable();
            $table->string('suggested_bid')->nullable();

            $table->string('ai_suggested_bid')->nullable();
            $table->string('ai_recommendation')->nullable();
            $table->string('ai_status')->nullable();

            $table->decimal('s_bid_min')->default(0);
            $table->decimal('s_bid_range')->default(0);
            $table->decimal('s_bid_max')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(
                ['keyword_id', 'date', 'country', 'campaign_types', 'campaign_id'],
                'uniq_kw_date_country_campaign'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('amz_keyword_recommendations');
    }
};
