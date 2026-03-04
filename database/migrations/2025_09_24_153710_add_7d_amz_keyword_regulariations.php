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
        Schema::table(
            'amz_keyword_recommendations',
            function (Blueprint $table) {
                $table->integer('clicks_7d')->default(0)->after('conversion_rate');
                $table->decimal('cpc_7d')->default(0)->after('clicks_7d');
                $table->decimal('ctr_7d')->default(0)->after('cpc_7d');
                $table->decimal('conversion_rate_7d')->default(0)->after('ctr_7d');
                $table->string('impressions_7d')->nullable()->after('conversion_rate_7d');
            }
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
