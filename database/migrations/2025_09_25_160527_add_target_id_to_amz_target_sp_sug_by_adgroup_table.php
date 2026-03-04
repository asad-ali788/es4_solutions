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
        Schema::table('amz_target_sp_sug_by_adgroup', function (Blueprint $table) {
            $table->string('target_id')->nullable()->after('ad_group_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('amz_target_sp_sug_by_adgroup', function (Blueprint $table) {
            $table->dropColumn('target_id');
        });
    }
};
