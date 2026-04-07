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
        Schema::create('targeting_report_bi', function (Blueprint $table) {
            $table->id();

            $table->date('report_date')->index();

            $table->string('campaign_name')->nullable();
            $table->string('portfolio_name')->nullable();

            $table->string('country', 10)->nullable()->index();
            $table->string('targeting')->nullable();
            $table->string('match_type', 100)->nullable();

            $table->unsignedInteger('impressions')->nullable();
            $table->unsignedInteger('clicks')->nullable();

            $table->decimal('spend', 12, 2)->nullable();
            $table->decimal('sales', 12, 2)->nullable();

            $table->unsignedInteger('units')->nullable();

            $table->timestamps();
            // composite index for performance
            $table->index(['report_date', 'country']);
            // composite unique index for upsert
            $table->unique([
                'report_date',
                'campaign_name',
                'targeting',
            ], 'targeting_report_bi_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('targeting_report_bi');
    }
};
