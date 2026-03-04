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
        Schema::create('campaign_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('asin')->nullable()->index();
            $table->json('sku')->nullable();
            $table->string('country', 10)->nullable()->index();

            $table->string('campaign_type', 5)->default('SP')->index();     // SP/SB/SD
            $table->string('targeting_type', 20)->default('MANUAL')->index(); // AUTO/MANUAL

            $table->json('campaigns')->nullable(); // generated campaigns + keywords
            $table->string('status', 20)->default('draft')->index();        // draft/submitted/failed

            $table->text('error')->nullable(); // store longer error (API payload/trace summary)

            $table->timestamps();
            $table->softDeletes(); // optional, but very useful
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaign_drafts');
    }
};
