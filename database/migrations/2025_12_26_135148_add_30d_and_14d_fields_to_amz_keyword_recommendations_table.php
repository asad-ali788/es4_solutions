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
        Schema::table('amz_keyword_recommendations', function (Blueprint $table) {

            // 14d
            $table->decimal('acos_14d', 6, 2)->nullable()->after('purchases1d_14d');

            // 30d
            $table->decimal('total_spend_30d', 15, 2)->nullable()->after('acos_14d');

            $table->decimal('total_sales_30d', 15, 2)->nullable()->after('total_spend_30d');

            $table->integer('purchases7d_30d')->nullable()->after('total_sales_30d');

            $table->decimal('acos_30d', 6, 2)->nullable()->after('purchases7d_30d');
        });
    }

    public function down(): void
    {
        Schema::table('amz_keyword_recommendations', function (Blueprint $table) {
            $table->dropColumn(['acos_14d', 'total_spend_30d', 'total_sales_30d', 'purchases7d_30d', 'acos_30d',]);
        });
    }
};
