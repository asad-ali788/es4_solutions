<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 🛠️ Clean up duplicates for SP keywords
        // $this->deleteDuplicates('amz_ads_keyword_performance_report', ['campaign_id', 'keyword_id', 'c_date', 'country']);

        Schema::table('amz_ads_keyword_performance_report', function (Blueprint $table) {
            $table->unique(['campaign_id', 'keyword_id', 'c_date', 'country'], 'amz_ads_keyword_perf_unique');
        });

        // 🛠️ Clean up duplicates for SB keywords
        // $this->deleteDuplicates('amz_ads_keyword_performance_report_sb', ['campaign_id', 'keyword_id', 'c_date', 'country']);

        Schema::table('amz_ads_keyword_performance_report_sb', function (Blueprint $table) {
            $table->unique(['campaign_id', 'keyword_id', 'c_date', 'country'], 'amz_ads_keyword_sb_perf_unique');
        });
    }

    private function deleteDuplicates(string $table, array $columns): void
    {
        $cols = implode(', ', $columns);
        DB::statement("
            DELETE t1 FROM {$table} t1
            INNER JOIN {$table} t2 
            WHERE t1.id < t2.id 
            AND " . implode(' AND ', array_map(fn($c) => "t1.{$c} = t2.{$c}", $columns))
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('amz_ads_keyword_performance_report', function (Blueprint $table) {
            $table->dropUnique('amz_ads_keyword_perf_unique');
        });

        Schema::table('amz_ads_keyword_performance_report_sb', function (Blueprint $table) {
            $table->dropUnique('amz_ads_keyword_sb_perf_unique');
        });
    }
};
