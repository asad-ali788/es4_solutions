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
        Schema::create('feed_logs', function (Blueprint $table) {
            $table->id();
            $table->string('feedDocID')->nullable();
            $table->string('country', 10)->nullable();
            $table->timestamp('feed_submit')->nullable();
            $table->text('feed_type')->nullable();
            $table->string('feed_id')->nullable();
            $table->json('feed_result_ID')->nullable();
            $table->enum('status', ['submitted', 'pending', 'success', 'failed'])->default('pending');
            $table->text('feed_summary')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feed_logs');
    }
};
