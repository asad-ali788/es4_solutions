<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('amz_campaigns', function (Blueprint $table) {
            $table->string('portfolio_id', 50)
                ->nullable()
                ->after('campaign_id')
                ->index();
        });

        Schema::table('amz_campaigns_sb', function (Blueprint $table) {
            $table->string('portfolio_id', 50)
                ->nullable()
                ->after('campaign_id')
                ->index();
        });
    }

    public function down(): void
    {
        Schema::table('amz_campaigns', function (Blueprint $table) {
            $table->dropIndex(['portfolio_id']);
            $table->dropColumn('portfolio_id');
        });

        Schema::table('amz_campaigns_sb', function (Blueprint $table) {
            $table->dropIndex(['portfolio_id']);
            $table->dropColumn('portfolio_id');
        });
    }
};
