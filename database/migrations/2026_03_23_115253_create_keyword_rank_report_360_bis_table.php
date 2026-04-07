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
        Schema::create('keyword_rank_report_360_bi', function (Blueprint $table): void {
            $table->id();

            $table->string('product', 255)->nullable()->index();
            $table->string('child', 255)->nullable()->index();
            $table->string('asin', 50)->nullable()->index();
            $table->string('keyword', 500)->nullable()->index();
            $table->string('match_type', 100)->nullable()->index();
            $table->string('search_volume', 100)->nullable();

            $table->date('report_date')->index();
            $table->string('rank_value', 100)->nullable();

            $table->timestamps();

            $table->unique(
                ['asin', 'keyword', 'match_type', 'report_date'],
                'rank_report_360_unique_daily_record'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('keyword_rank_report_360_bi');
    }
};
