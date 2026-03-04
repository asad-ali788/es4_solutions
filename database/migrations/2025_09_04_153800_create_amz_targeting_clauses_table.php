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
        Schema::create('amz_targeting_clauses', function (Blueprint $table) {
            $table->id();
            $table->string('country', 3)->nullable();
            $table->bigInteger('target_id')->nullable();
            $table->bigInteger('ad_group_id')->nullable();
            $table->bigInteger('campaign_id')->nullable();
            $table->string('expression')->nullable();
            $table->string('expression_val')->nullable();
            $table->string('state')->nullable();
            $table->decimal('bid', 10, 2)->nullable();
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
        Schema::dropIfExists('amz_targeting_clauses');
    }
};
