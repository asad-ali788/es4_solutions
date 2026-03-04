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
        Schema::create('amz_campaigns_sb', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('campaign_id')->unique();
            $table->string('country')->nullable();
            $table->string('campaign_name', 400)->nullable();
            $table->string('campaign_type', 200)->nullable();
            $table->string('targeting_type', 150)->nullable();
            $table->decimal('daily_budget', 10, 2)->nullable();
            $table->dateTime('start_date')->nullable();
            $table->string('campaign_state', 150)->nullable();
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
        Schema::dropIfExists('amz_campaigns_sb');
    }
};
