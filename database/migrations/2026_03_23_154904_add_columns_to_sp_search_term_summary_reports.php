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
        Schema::table('sp_search_term_summary_reports', function (Blueprint $table) {
            $table->string('product_name')->nullable()->after('search_term');
            $table->string('asin')->nullable()->after('product_name');
            $table->string('keyword_name')->nullable()->after('asin');
            $table->string('campaign_name')->nullable()->after('keyword_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sp_search_term_summary_reports', function (Blueprint $table) {
            $table->dropColumn(['product_name', 'asin', 'keyword_name', 'campaign_name']);
        });
    }
};
