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
        Schema::create('amz_keyword_sp_sug_by_adgroup', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('ad_group_id');
            $table->bigInteger('campaign_id');
            $table->bigInteger('keyword_id');
            $table->string('keyword_text');
            $table->decimal('bid_start', 10, 2);
            $table->decimal('bid_suggestion', 10, 2);
            $table->decimal('bid_end', 10, 2);
            $table->string('match_type');
            $table->string('country', 3);
            $table->date('added');
            $table->boolean('is_processed')->default(false)->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sp_keyword_sug_by_adgroup');
    }
};
