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
        Schema::create('modified_category_rank_history_bis', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->date('date');
            $blueprint->string('asin');
            $blueprint->string('category_type');
            $blueprint->string('category_name')->nullable();
            $blueprint->timestamps();

            // Unique Index for idempotent sync
            $blueprint->unique(['date', 'asin', 'category_type'], 'modified_cat_rank_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('modified_category_rank_history_bis');
    }
};
