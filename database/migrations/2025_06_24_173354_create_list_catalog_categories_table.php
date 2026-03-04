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
        Schema::create('list_catalog_categories', function (Blueprint $table) {
            $table->id();
            $table->string('asin')->nullable();
            $table->string('marketplace_id')->nullable();
            $table->string('seller_sku')->nullable();
            $table->string('catalog_categories')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('list_catalog_categories');
    }
};
