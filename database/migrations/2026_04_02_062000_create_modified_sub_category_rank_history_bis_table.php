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
        Schema::create('modified_sub_category_rank_history_bis', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->date('date');
            $blueprint->string('asin');
            $blueprint->string('category_type');
            $blueprint->string('sub_category_name')->nullable();
            $blueprint->integer('sub_category_rank')->nullable();
            $blueprint->timestamps();

            // Unique Index for idempotent sync
            $blueprint->unique(['date', 'asin', 'category_type', 'sub_category_name'], 'mod_sub_cat_rank_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('modified_sub_category_rank_history_bis');
    }
};
