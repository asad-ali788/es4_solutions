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
        Schema::create('amz_ads_keywords', function (Blueprint $table) {
            $table->id();
            $table->string('keyword_id')->unique();
            $table->bigInteger('campaign_id')->nullable();
            $table->string('country')->nullable();
            $table->string('ad_group_id')->nullable();
            $table->string('keyword_text')->nullable();
            $table->string('match_type', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->decimal('bid', 10, 2)->nullable();
            $table->dateTime('added')->nullable();
            $table->dateTime('updated')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('amz_ads_keywords');
    }
};
