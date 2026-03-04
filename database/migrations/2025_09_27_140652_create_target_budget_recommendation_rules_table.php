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
        Schema::create('target_budget_recommendation_rule', function (Blueprint $table) {
            $table->id();
            $table->decimal('min_ctr', 5, 2)->nullable();
            $table->decimal('max_ctr', 5, 2)->nullable();
            $table->decimal('min_conversion_rate', 5, 2)->nullable();
            $table->decimal('max_conversion_rate', 5, 2)->nullable();
            $table->decimal('min_acos', 5, 2)->nullable();
            $table->decimal('max_acos', 5, 2)->nullable();
            $table->unsignedInteger('min_clicks')->nullable();
            $table->unsignedInteger('min_sales')->nullable();
            $table->unsignedInteger('min_impressions')->nullable();
            $table->string('action_label')->comment('Recommendation text shown to user');
            $table->enum('adjustment_type', ['increase', 'decrease', 'keep'])->default('keep');
            $table->decimal('adjustment_value', 5, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('priority')->default(100);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('target_budget_recommendation_rule');
    }
};
