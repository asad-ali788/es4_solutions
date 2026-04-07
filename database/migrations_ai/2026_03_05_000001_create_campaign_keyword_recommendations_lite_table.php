<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This table stores lightweight, denormalized keyword recommendations for campaigns
     * optimized for fast AI querying without needing to join multiple tables.
     * 
     * Data flow:
     * - Base: campaign_keyword_recommendations (keywords, bids, match types)
     * - Add: amz_campaigns (campaign_name, campaign_type from campaign_id)
     * - Add: amz_ads_products (asin from campaign_id)
     */
    public function up(): void
    {
        Schema::create('campaign_keyword_recommendations_lite', function (Blueprint $table) {
            $table->id();
            
            // Campaign info (from campaign_keyword_recommendations + amz_campaigns join)
            $table->string('campaign_id', 50)->index();
            $table->string('campaign_name', 400)->nullable()->index();
            $table->string('campaign_type', 20)->nullable()->index(); // SP, SB, SD (primarily SP)
            
            // Keyword info (from campaign_keyword_recommendations)
            $table->string('keyword')->index();
            $table->string('match_type', 20)->nullable(); // EXACT, PHRASE, BROAD, NEGATIVE_EXACT, etc.
            
            // Product info (from amz_ads_products)
            $table->string('asin', 20)->nullable()->index(); // Primary ASIN from campaign
            
            // Bid information
            $table->decimal('current_bid', 10, 2)->nullable();
            $table->decimal('bid_suggestion_start', 10, 2)->nullable(); // bid_start from campaign_keyword_recommendations
            $table->decimal('bid_suggestion_mid', 10, 2)->nullable();   // bid_suggestion from campaign_keyword_recommendations
            $table->decimal('bid_suggestion_end', 10, 2)->nullable();   // bid_end from campaign_keyword_recommendations
            
            // Geographic info
            $table->string('country', 5)->nullable()->index();
            
            // Ad group relation (optional - can be used for hierarchy queries)
            $table->string('ad_group_id', 50)->nullable()->index();
            
            // Metadata for tracking
            $table->timestamp('synced_at')->nullable(); // When this was last synced
            
            // Indexes for common query patterns
            $table->unique(['campaign_id', 'keyword', 'match_type', 'country'], 'ckrl_unique_campaign_keyword_match_country');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaign_keyword_recommendations_lite');
    }
};
