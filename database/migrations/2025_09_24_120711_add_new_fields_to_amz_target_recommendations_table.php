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
        Schema::table('amz_target_recommendations', function (Blueprint $table) {
            $table->decimal('total_spend_7d', 12, 2)->nullable()->after('total_spend');
            $table->decimal('total_sales_7d', 12, 2)->nullable()->after('total_sales');
            $table->decimal('acos_7d', 5, 2)->nullable()->after('acos');

            $table->decimal('total_spend_14d', 12, 2)->nullable()->after('total_spend_7d');
            $table->decimal('total_sales_14d', 12, 2)->nullable()->after('total_sales_7d');

            $table->integer('purchases1d')->nullable()->after('orders');
            $table->integer('purchases1d_7d')->nullable()->after('purchases1d');
            $table->integer('purchases1d_14d')->nullable()->after('purchases1d_7d');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('amz_target_recommendations', function (Blueprint $table) {
            $table->dropColumn([
                'total_spend_7d',
                'total_sales_7d',
                'acos_7d',
                'total_spend_14d',
                'total_sales_14d',
                'purchases1d',
                'purchases1d_7d',
                'purchases1d_14d',
            ]);
        });
    }
};
