<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('hourly_sales', function (Blueprint $table) {
            $table->dateTime('sale_date')->nullable()->change();
        });

        Schema::table('hourly_sales_snapshots', function (Blueprint $table) {
            $table->dateTime('snapshot_time')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('hourly_sales', function (Blueprint $table) {
            $table->timestamp('sale_date')->nullable()->change();
        });

        Schema::table('hourly_sales_snapshots', function (Blueprint $table) {
            $table->timestamp('snapshot_time')->nullable()->change();
        });
    }
};
