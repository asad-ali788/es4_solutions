<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAmzReportsLogsTable extends Migration
{
    public function up(): void
    {
        Schema::create('amz_reports_log', function (Blueprint $table) {
            $table->id();
            $table->string('report_type');
            $table->string('report_id');
            $table->string('report_document_id')->nullable();
            $table->enum('report_status', ['IN_PROGRESS', 'DONE', 'FATAL', 'CANCELLED'])->default('IN_PROGRESS');
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->json('marketplace_ids')->nullable();
            $table->timestamps(); 
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('amz_reports_log');
    }
}
