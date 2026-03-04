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
        Schema::connection('ai_sqlite')->create('keyword_campaign_performance_lites', function (Blueprint $table) {
            // Primary identifiers
            $table->id();
            $table->string('keyword_text')->index();
            $table->string('campaign_name')->index();
            $table->string('campaign_id')->index();
            $table->string('asin')->nullable()->index();
            $table->string('product_name')->nullable()->index(); // child_short_name from product_categorisation
            $table->string('country')->index();
            $table->string('campaign_type')->index(); // SP, SB, SD
            $table->string('campaign_state')->default('ENABLED');
            
            // Report date (single date column)
            $table->date('report_date')->index();
            
            // Budget information
            $table->decimal('daily_budget', 10, 2)->default(0);
            $table->decimal('estimated_monthly_budget', 12, 2)->default(0);
            
            // 1-day metrics (current day aggregates)
            $table->decimal('total_spend', 12, 2)->default(0);
            $table->decimal('total_sales', 12, 2)->default(0);
            $table->decimal('acos', 8, 4)->default(0);
            $table->integer('purchases')->default(0);
            $table->integer('clicks')->default(0);
            $table->integer('impressions')->default(0);
            $table->decimal('cpc', 8, 4)->default(0);
            $table->decimal('ctr', 8, 4)->default(0);
            
            // Calculated metrics
            $table->decimal('roas', 8, 4)->nullable(); // 100 / ACOS
            $table->decimal('conversion_rate', 8, 4)->default(0); // purchases / clicks
            
            // Keyword specifics
            $table->decimal('keyword_bid', 10, 4)->nullable();
            $table->string('keyword_state')->default('ENABLED'); // ENABLED, PAUSED, ARCHIVED
            $table->decimal('keyword_cpc', 8, 4)->nullable();
            $table->integer('keyword_match_type')->default(0); // EXACT, PHRASE, BROAD
            
            // Additional fields
            $table->decimal('product_price', 10, 2)->nullable();
            $table->integer('product_rating')->nullable();
            $table->integer('product_review_count')->nullable();
            
            // Metadata
            $table->json('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Composite unique constraint to prevent duplicates
            $table->unique(['keyword_text', 'campaign_id', 'report_date', 'country'], 'unique_keyword_campaign_date');
            
            // Indexes for common queries
            $table->index(['campaign_name', 'report_date']);
            $table->index(['keyword_text', 'campaign_type']);
            $table->index(['country', 'campaign_type', 'report_date']);
            $table->index(['asin', 'report_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('ai_sqlite')->dropIfExists('keyword_campaign_performance_lites');
    }
};
