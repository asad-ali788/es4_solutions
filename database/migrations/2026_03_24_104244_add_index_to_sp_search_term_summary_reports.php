<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sp_search_term_summary_reports', function (Blueprint $table): void {
            $table->unique(
                [
                    'country',
                    'date',
                    'campaign_id',
                    'ad_group_id',
                    'keyword_id',
                    'search_term',
                ],
                'stsr_upsert_unique_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('sp_search_term_summary_reports', function (Blueprint $table): void {
            $table->dropUnique('stsr_upsert_unique_idx');
        });
    }
};
