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
        Schema::table('keyword_sug_sb_video', function (Blueprint $table) {
            $table->renameColumn('bid_start', 'key_bid_start');
            $table->renameColumn('bid_suggestion', 'key_bid_suggestion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('keyword_sug_sb_video', function (Blueprint $table) {
            $table->renameColumn('key_bid_start', 'bid_start');
            $table->renameColumn('key_bid_suggestion', 'bid_suggestion');
        });
    }
};
