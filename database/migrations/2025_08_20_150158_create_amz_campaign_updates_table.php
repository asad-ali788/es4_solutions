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
        Schema::create('amz_campaign_updates', function (Blueprint $table) {
            $table->id();
            $table->string('campaign_id');
            $table->string('campaign_type');
            $table->string('country');
            $table->decimal('old_budget', 10, 2)->nullable();
            $table->decimal('new_budget', 10, 2)->nullable();
            $table->string('old_status')->nullable();
            $table->string('new_status')->nullable();
            $table->foreignId('updated_by')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('iteration')->default(1);
            $table->string('status')->default('pending'); // pending/success/failed
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
        Schema::dropIfExists('amz_campaign_updates');
    }
};
