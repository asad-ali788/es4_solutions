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
        Schema::create('amz_targets_sd', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('target_id')->nullable();
            $table->bigInteger('ad_group_id')->nullable();
            $table->bigInteger('campaign_id')->nullable();
            $table->string('state')->nullable();
            $table->decimal('bid', 10, 4)->nullable();
            $table->string('expression_type')->nullable();
            $table->json('expression')->nullable();
            $table->json('resolved_expression')->nullable();
            $table->string('region')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('amz_targets_sd');
    }
};
