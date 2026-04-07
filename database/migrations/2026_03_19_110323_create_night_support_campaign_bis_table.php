<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('night_support_campaign_bi', function (Blueprint $table) {
            $table->id();

            // Core Dimensions
            $table->date('report_date')->index();
            $table->string('campaign', 200)->nullable()->index();
            $table->string('state', 50)->nullable()->index();
            $table->string('status', 50)->nullable()->index();
            $table->string('type', 50)->nullable()->index();
            $table->string('targeting', 200)->nullable()->index();

            // Metrics
            $table->unsignedBigInteger('impressions')->nullable();
            $table->unsignedInteger('clicks')->nullable();
            $table->decimal('ctr', 8, 4)->nullable();
            $table->decimal('spend_usd', 12, 2)->nullable();
            $table->decimal('cpc_usd', 10, 4)->nullable();
            $table->unsignedInteger('orders')->nullable();
            $table->decimal('sales_usd', 12, 2)->nullable();
            $table->unsignedInteger('units_sold')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // 🔥 Composite indexes (important for BI queries)
            $table->index(['report_date', 'campaign']);
            $table->index(['report_date', 'state']);
            $table->index(['report_date', 'type']);
            // Composite unique index for upsert
            $table->unique([
                'report_date',
                'campaign',
                'type',
                'targeting',
            ], 'night_support_campaign_bi_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('night_support_campaign_bi');
    }
};