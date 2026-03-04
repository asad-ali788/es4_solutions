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
        Schema::table('order_forecasts', function (Blueprint $table) {

            $table->enum('status_flag', ['pending', 'ready', 'failed', 'draft'])
                ->default('draft')
                ->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_forecasts', function (Blueprint $table) {
            //
        });
    }
};
