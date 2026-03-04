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
        Schema::create('sp_search_term_summary_reports', function (Blueprint $table) {
            $table->id();
            
            $table->unsignedBigInteger('campaign_id')->nullable();
            $table->unsignedBigInteger('ad_group_id')->nullable();
            $table->unsignedBigInteger('keyword_id')->nullable();

            $table->string('country', 5)->index();    
            $table->date('date')->index();            

            $table->string('keyword')->nullable();
            $table->string('search_term')->nullable();

            $table->integer('impressions')->nullable();
            $table->integer('clicks')->nullable();
            $table->decimal('cost_per_click', 12, 4)->nullable();
            $table->decimal('cost', 12, 4)->nullable();

            $table->integer('purchases_1d')->nullable();
            $table->integer('purchases_7d')->nullable();
            $table->integer('purchases_14d')->nullable();

            $table->decimal('sales_1d', 14, 4)->nullable();
            $table->decimal('sales_7d', 14, 4)->nullable();
            $table->decimal('sales_14d', 14, 4)->nullable();

            $table->decimal('campaign_budget_amount', 14, 4)->nullable();
            $table->decimal('keyword_bid', 14, 4)->nullable();

            $table->string('keyword_type')->nullable();
            $table->string('match_type')->nullable();
            $table->string('targeting')->nullable();
            $table->string('ad_keyword_status')->nullable();

            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sp_search_term_summary_reports');
    }
};
