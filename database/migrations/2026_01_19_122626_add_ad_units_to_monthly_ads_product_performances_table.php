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
        Schema::table('monthly_ads_product_performances', function (Blueprint $table) {
            $table->unsignedInteger('ad_units')->nullable()->comment('purchase values')->after('ad_sales');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monthly_ads_product_performances', function (Blueprint $table) {
            $table->dropColumn('ad_units');
        });
    }
};
