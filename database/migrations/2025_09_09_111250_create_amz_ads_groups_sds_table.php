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
        Schema::create('amz_ads_groups_sd', function (Blueprint $table) {
            $table->id();
            $table->string('country', 5)->nullable();
            $table->bigInteger('ad_group_id')->nullable();
            $table->bigInteger('campaign_id')->nullable();
            $table->string('name')->nullable();
            $table->decimal('default_bid', 12, 2)->nullable();
            $table->string('bid_optimization')->nullable();
            $table->string('state')->nullable();
            $table->string('tactic')->nullable();
            $table->string('creative_type')->nullable();
            $table->date('added')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('amz_ads_groups_sd');
    }
};
