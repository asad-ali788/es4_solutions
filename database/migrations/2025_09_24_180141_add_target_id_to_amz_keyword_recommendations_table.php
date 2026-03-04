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
            $table->string('target_id', 50)->nullable()->after('keyword_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('amz_keyword_recommendations', function (Blueprint $table) {
            $table->dropColumn('target_id');
        });
    }
};
