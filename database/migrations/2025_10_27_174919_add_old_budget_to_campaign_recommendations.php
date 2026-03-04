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
        Schema::table('campaign_recommendations', function (Blueprint $table) {
            $table->decimal('old_budget', 12, 2)->nullable()->after('manual_budget');
            $table->unique(
                ['campaign_id', 'report_week', 'campaign_types'],
                'cr_campaign_week_type_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaign_recommendations', function (Blueprint $table) {
            //
        });
    }
};
