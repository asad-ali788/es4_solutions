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
        Schema::create('amz_ads_campaigns_budget_usages', function (Blueprint $table) {
            $table->id();
            $table->string('campaign_id', 50)->index();
            $table->string('campaign_type');
            $table->decimal('budget', 12, 2)->default(0);
            $table->decimal('budget_usage_percent', 5, 2)->default(0);
            $table->timestamp('usage_updated_at')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(
                ['campaign_id', 'campaign_type', 'usage_updated_at'],
                'uniq_campaign_type_budget_usage'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('amz_ads_campaigns_budget_usages');
    }
};
