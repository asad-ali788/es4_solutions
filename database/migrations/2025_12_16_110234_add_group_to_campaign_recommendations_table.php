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
            $table->unsignedTinyInteger('from_group')->after('country');
            $table->unsignedTinyInteger('to_group')->after('from_group');
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
