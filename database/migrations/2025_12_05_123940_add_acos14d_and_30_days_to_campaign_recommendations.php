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
            $table->decimal('acos_14d', 6, 2)->nullable()->after('purchases7d_14d');

            $table->decimal('total_spend_30d', 15, 2)->nullable()->after('acos_14d');
            $table->decimal('total_sales_30d', 15, 2)->nullable()->after('total_spend_30d');
            $table->integer('purchases7d_30d')->nullable()->after('total_sales_30d');
            $table->decimal('acos_30d', 6, 2)->nullable()->after('purchases7d_30d');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaign_recommendations', function (Blueprint $table) {
            //
        });
    }
};
