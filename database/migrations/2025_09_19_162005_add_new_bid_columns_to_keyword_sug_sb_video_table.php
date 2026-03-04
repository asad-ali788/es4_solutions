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
            $table->decimal('key_bid_end', 10, 2)->nullable()->after('key_bid_start');
            $table->decimal('target_bid_start', 10, 2)->nullable()->after('key_bid_end');
            $table->decimal('target_bid_end', 10, 2)->nullable()->after('target_bid_start');
            $table->decimal('target_bid_suggestion', 10, 2)->nullable()->after('target_bid_end');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('keyword_sug_sb_video', function (Blueprint $table) {
            $table->dropColumn([
                'key_bid_end',
                'target_bid_start',
                'target_bid_end',
                'target_bid_suggestion',
            ]);
        });
    }
};
