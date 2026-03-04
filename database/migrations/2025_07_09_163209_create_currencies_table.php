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
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('country_code', 2)->unique(); // ISO alpha-2 (e.g. US, UK)
            $table->string('currency_code', 3);          // ISO 4217 (e.g. USD, GBP)
            $table->string('currency_name');
            $table->string('currency_symbol', 10)->nullable();
            $table->decimal('conversion_rate_to_usd', 6, 2)->default(1.00);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
