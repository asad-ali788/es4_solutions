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
        Schema::create('amz_ads_campaign_performance_snapshots', function (Blueprint $table) {
            $table->id();
            $table->string('campaign_types', 20)->nullable();
            $table->decimal('total_spend', 12, 2)->nullable();
            $table->decimal('total_sales', 12, 2)->nullable();
            $table->integer('total_units')->nullable();
            $table->decimal('acos', 6, 2)->nullable();
            $table->string('country', 3)->nullable();
            $table->timestamp('snapshot_time')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('amz_ads_campaign_performance_snapshots');
    }
};
