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
        Schema::create('amz_ads_campaigns_under_schedules', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('campaign_id');
            $table->string('country', 3);
            $table->string('campaign_type', 100)->nullable();
            $table->string('campaign_status', 50)->nullable();
            $table->unsignedTinyInteger('run_status');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->date('added');
            $table->dateTime('last_updated');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('amz_ads_campaigns_under_schedules');
    }
};
