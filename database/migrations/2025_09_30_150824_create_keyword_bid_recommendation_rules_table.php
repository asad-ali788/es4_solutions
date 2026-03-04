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
        Schema::create('keyword_bid_recommendation_rules', function (Blueprint $table) {
            $table->id();

            $table->string('ctr_condition')->nullable()->comment('CTR threshold in percentage (e.g., 1 = 1%)');
            $table->string('conversion_condition')->nullable()->comment('Conversion rate threshold in percentage (e.g., 15 = 15%)');
            $table->string('acos_condition')->nullable()->comment('ACoS threshold in percentage (e.g., 30 = 30%)');
            $table->string('click_condition')->nullable()->comment('Minimum or maximum number of clicks to trigger this rule');
            $table->string('sales_condition')->nullable()->comment('Sales threshold (used to determine when to apply rule)');
            $table->string('impressions_condition')->nullable()->comment('Impressions threshold (used to determine when to apply rule)');
            $table->string('action_label')->comment('Description of the recommendation action to display to the user');
            $table->string('bid_adjustment')->nullable()->comment('Multiplier or "Pause" for bid adjustments');

            // Priority now unique
            $table->unsignedInteger('priority')->unique()->comment('Unique priority for rule ordering');
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('keyword_bid_recommendation_rules');
    }
};
