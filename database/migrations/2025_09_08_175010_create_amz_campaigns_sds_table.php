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
        Schema::create('amz_campaigns_sd', function (Blueprint $table) {
            $table->id();
            $table->string('country', 5);
            $table->bigInteger('campaign_id')->nullable();
            $table->bigInteger('portfolio_id')->nullable();
            $table->string('campaign_name')->nullable();
            $table->string('campaign_type')->nullable();
            $table->string('targeting_type')->nullable();
            $table->decimal('daily_budget', 12, 2)->nullable();
            $table->date('start_date')->nullable();
            $table->string('campaign_state')->nullable();
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
        Schema::dropIfExists('amz_campaigns_sd');
    }
};
