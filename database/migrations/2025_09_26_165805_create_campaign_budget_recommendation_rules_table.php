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
        Schema::create('campaign_budget_recommendation_rules', function (Blueprint $table) {
            $table->id();
            $table->decimal('min_acos', 5, 2)->nullable()->comment('Minimum ACOS % threshold');
            $table->decimal('max_acos', 5, 2)->nullable()->comment('Maximum ACOS % threshold (NULL = no limit)');
            $table->enum('spend_condition', ['gte_budget', 'lt_budget', 'any'])
                ->default('any')
                ->comment('Condition comparing spend vs daily budget');
            $table->string('action_label')->comment('Recommendation text shown to user');
            $table->enum('adjustment_type', ['increase', 'decrease', 'keep'])
                ->default('keep')
                ->comment('Budget adjustment type');
            $table->decimal('adjustment_value', 5, 2)->nullable()->comment('Percentage for increase/decrease (NULL if keep)');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('priority')->default(100)->comment('Lower number = higher priority');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaign_budget_recommendation_rules');
    }
};
