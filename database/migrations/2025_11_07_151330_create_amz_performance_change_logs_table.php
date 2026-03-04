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
        Schema::create('amz_performance_change_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('campaign_id')->nullable();
            $table->unsignedBigInteger('keyword_id')->nullable();
            $table->unsignedBigInteger('target_id')->nullable();
            $table->enum('change_type', ['campaign', 'keyword', 'target']);
            $table->string('country', 5)->nullable();
            $table->date('date')->nullable();
            $table->decimal('old_value', 10, 2)->nullable();
            $table->decimal('new_value', 10, 2)->nullable();
            $table->string('type', 10)->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('amz_performance_change_logs');
    }
};
