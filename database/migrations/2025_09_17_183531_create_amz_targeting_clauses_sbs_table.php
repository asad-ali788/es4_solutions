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
        Schema::create('amz_targeting_clauses_sb', function (Blueprint $table) {
            $table->id();
            $table->string('target_id')->nullable();
            $table->string('campaign_id')->nullable();
            $table->string('ad_group_id')->nullable();
            $table->string('country', 5)->nullable();
            $table->decimal('bid', 12, 4)->nullable();
            $table->json('expressions')->nullable();
            $table->json('resolved_expressions')->nullable();
            $table->string('state')->nullable();
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
        Schema::dropIfExists('amz_targeting_clauses_sb');
    }
};
