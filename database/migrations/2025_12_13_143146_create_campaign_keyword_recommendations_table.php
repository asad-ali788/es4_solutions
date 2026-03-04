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
        Schema::create('campaign_keyword_recommendations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('campaign_id')->nullable();
            $table->unsignedBigInteger('ad_group_id')->nullable();
            $table->string('keyword')->nullable();
            $table->string('match_type')->nullable();
            $table->decimal('bid', 10, 2)->nullable();
            $table->decimal('bid_start', 10, 2)->nullable();
            $table->decimal('bid_suggestion', 10, 2)->nullable();
            $table->decimal('bid_end', 10, 2)->nullable();
            $table->string('country', 3)->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(
                ['campaign_id', 'ad_group_id', 'keyword', 'match_type', 'country'],
                'ckr_unique_campaign_adgroup_keyword_match_country'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaign_keyword_recommendations');
    }
};
