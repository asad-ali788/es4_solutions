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
        Schema::create('amz_target_recommendations', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('targeting_id')->nullable();
            $table->string('targeting_text')->nullable();
            $table->bigInteger('campaign_id')->nullable();
            $table->bigInteger('ad_group_id')->nullable();
            $table->date('date')->nullable();

            $table->integer('clicks')->nullable();
            $table->decimal('cpc', 10, 2)->nullable();
            $table->decimal('ctr', 5, 2)->nullable();
            $table->integer('orders')->nullable();
            $table->decimal('total_spend', 12, 2)->nullable();
            $table->decimal('total_sales', 12, 2)->nullable();
            $table->decimal('conversion_rate', 5, 2)->nullable();
            $table->decimal('acos', 5, 2)->nullable();

            $table->string('campaign_types')->nullable(); // SD or SB
            $table->string('country')->nullable();
            $table->text('recommendation')->nullable();
            $table->integer('impressions')->nullable();
            $table->decimal('suggested_bid', 10, 2)->nullable();
            $table->decimal('ai_suggested_bid', 10, 2)->nullable();
            $table->decimal('s_bid_min', 10, 2)->nullable();
            $table->decimal('s_bid_range', 10, 2)->nullable();
            $table->decimal('s_bid_max', 10, 2)->nullable();
            $table->text('ai_recommendation')->nullable();
            $table->string('ai_status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('amz_target_recommendations');
    }
};
