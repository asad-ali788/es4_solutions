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
            $table->decimal('manual_budget', 10, 2)->nullable()->after('suggested_budget');
            $table->boolean('run_update')->default(false)->after('manual_budget');
            $table->enum('run_status', ['pending', 'dispatched', 'failed', 'done'])->default('pending')->after('run_update');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaign_recommendations', function (Blueprint $table) {
            $table->dropColumn('manual_budget');
        });
    }
};
