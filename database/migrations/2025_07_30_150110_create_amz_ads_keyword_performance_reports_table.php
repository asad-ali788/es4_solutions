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
        Schema::create('amz_ads_keyword_performance_report', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('campaign_id')->nullable();
            $table->bigInteger('ad_group_id')->nullable();
            $table->bigInteger('keyword_id')->nullable();
            $table->decimal('cost', 10, 2)->nullable();
            $table->decimal('sales1d', 10, 2)->nullable();
            $table->decimal('sales7d', 10, 2)->nullable();
            $table->decimal('sales30d', 20, 2)->nullable();
            $table->integer('purchases1d')->nullable();
            $table->integer('purchases7d')->nullable();
            $table->bigInteger('purchases30d')->nullable();
            $table->integer('clicks')->nullable();
            $table->bigInteger('impressions')->nullable();
            $table->decimal('keyword_bid', 10, 2)->nullable();
            $table->string('targeting')->nullable();
            $table->string('keyword_text')->nullable();
            $table->string('match_type')->nullable();
            $table->dateTime('c_date')->nullable();
            $table->string('country')->nullable();
            $table->dateTime('added')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('amz_ads_keyword_performance_report');
    }
};
