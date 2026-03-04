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
        Schema::create('keyword_sug_sb_video', function (Blueprint $table) {
            $table->id();
            $table->string('country', 3)->nullable();
            $table->bigInteger('ad_group_id')->nullable();
            $table->bigInteger('campaign_id')->nullable();
            $table->bigInteger('keyword_id')->nullable();
            $table->string('keyword_text')->nullable();
            $table->decimal('bid_start', 10, 2)->nullable();
            $table->decimal('bid_suggestion', 10, 2)->nullable();
            $table->string('match_type')->nullable();
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
        Schema::dropIfExists('keyword_sug_sb_video');
    }
};
