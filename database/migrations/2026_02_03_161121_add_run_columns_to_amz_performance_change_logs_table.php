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
        Schema::table('amz_performance_change_logs', function (Blueprint $table) {
            $table->boolean('run_update')->default(false)->after('date');

            $table->enum('run_status', ['pending', 'dispatched', 'failed', 'reverted',])->default('pending')->after('run_update');

            $table->integer('reverted_by')->nullable()->after('run_status');

            $table->timestamp('revert_executed_at')->nullable()->after('reverted_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('amz_performance_change_logs', function (Blueprint $table) {
            $table->dropColumn([
                'run_update',
                'run_status',
                'reverted_by',
                'revert_executed_at',
            ]);
        });
    }
};
