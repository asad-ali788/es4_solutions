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
        Schema::create('amz_target_sp_sug_by_adgroup', function (Blueprint $table) {
            $table->id();
            $table->string('campaign_id')->nullable();
            $table->string('ad_group_id')->nullable();
            $table->string('theme')->nullable();

            $table->string('target_type')->nullable();
            $table->string('match_type')->nullable();
            $table->string('keyword_text')->nullable();

            $table->decimal('bid_start', 12, 4)->nullable();
            $table->decimal('bid_median', 12, 4)->nullable();
            $table->decimal('bid_end', 12, 4)->nullable();

            $table->string('country')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('amz_target_sp_sug_by_adgroup');
    }
};
