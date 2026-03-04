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
        Schema::table('target_budget_recommendation_rule', function (Blueprint $table) {
            $table->enum('adjustment_type', ['increase', 'decrease', 'keep', 'pause'])
                ->default('keep')
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('target_budget_recommendation_rule', function (Blueprint $table) {
            $table->enum('adjustment_type', ['increase', 'decrease', 'keep'])
                ->default('keep')
                ->change();
        });
    }
};
