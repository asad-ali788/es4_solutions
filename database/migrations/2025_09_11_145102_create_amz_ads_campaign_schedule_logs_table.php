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
        Schema::create('amz_ads_campaign_schedule_logs', function (Blueprint $table) {
            $table->id();
            $table->string('country', 3);
            $table->enum('action', ['enabled', 'paused']);
            $table->timestamp('executed_at');
            $table->enum('status', ['success', 'failed'])->default('success');
            $table->text('payload_request')->nullable();
            $table->text('api_response')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('amz_ads_campaign_schedule_logs');
    }
};
