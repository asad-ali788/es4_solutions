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
        Schema::create('amz_ads_targets_performance_report_sd', function (Blueprint $table) {
            $table->id();
            $table->string('targeting_id')->nullable();
            $table->text('targeting_text')->nullable();
            $table->text('targeting_expression')->nullable();

            $table->string('campaign_id')->nullable();

            $table->string('ad_group_id')->nullable();

            $table->bigInteger('clicks')->nullable();
            $table->bigInteger('impressions')->nullable();
            $table->decimal('cost', 15, 4)->nullable();
            $table->decimal('sales', 15, 4)->nullable();
            $table->bigInteger('purchases')->nullable();
            $table->bigInteger('units_sold')->nullable();

            $table->date('c_date')->nullable();
            $table->string('country', 10)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('amz_ads_targets_performance_report_sd');
    }
};
