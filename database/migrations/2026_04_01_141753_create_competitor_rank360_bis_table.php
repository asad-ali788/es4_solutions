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
        Schema::create('competitor_rank_360_bi', function (Blueprint $table) {
            $table->id();
            $table->string('asin')->nullable();
            $table->string('keyword')->nullable();
            $table->string('rank_value')->nullable();
            $table->date('report_date');
            
            $table->timestamps();

            $table->unique(['asin', 'keyword', 'report_date'], 'comp_rank_360_unique');
            $table->index('report_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('competitor_rank_360_bi');
    }
};
