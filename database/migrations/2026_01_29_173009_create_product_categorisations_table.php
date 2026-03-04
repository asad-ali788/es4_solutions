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
        Schema::create('product_categorisations', function (Blueprint $table) {
            $table->id();
            $table->string('parent_short_name')->nullable()->index();
            $table->string('child_short_name')->nullable()->index();
            $table->string('parent_asin')->nullable()->index();
            $table->string('child_asin')->nullable()->index();
            $table->string('marketplace')->nullable();
            $table->timestamps();
            $table->softDeletes();
            // Prevent duplicates from sync
            $table->unique(
                ['child_asin', 'marketplace'],
                'pc_parent_child_market_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_categorisations');
    }
};
