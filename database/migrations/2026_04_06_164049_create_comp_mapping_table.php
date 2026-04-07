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
        Schema::create('comp_mapping', function (Blueprint $table) {
            $table->id();
            $table->string('asin')->index();
            $table->string('comp_asin')->index();
            $table->string('brand')->nullable();
            $table->timestamps();

            // Unique index for idempotent sync
            $table->unique(['asin', 'comp_asin'], 'comp_mapping_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comp_mapping');
    }
};
