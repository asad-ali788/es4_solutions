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
        Schema::create('amz_ads_report_log', function (Blueprint $table) {
            $table->id();
            $table->string('country')->nullable();
            $table->string('report_id')->nullable();
            $table->string('report_type')->nullable();
            $table->string('report_status')->nullable();
            $table->dateTime('report_date')->nullable();
            $table->text('r_iteration')->nullable();
            $table->dateTime('added')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('amz_ads_report_log');
    }
};
