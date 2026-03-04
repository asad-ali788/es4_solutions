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
            $table->decimal('old_bid', 12, 2)->nullable()->after('manual_bid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('amz_keyword_recommendations', function (Blueprint $table) {
            $table->dropColumn(['old_bid']);
        });
    }
};
