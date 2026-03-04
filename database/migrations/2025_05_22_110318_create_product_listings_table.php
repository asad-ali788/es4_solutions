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
        Schema::create('product_listings', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique(); // UUID column
            $table->foreignId('products_id')->constrained('products')->onDelete('cascade');
            $table->string('translator')->nullable();
            $table->string('title_amazon')->nullable();
            $table->text('bullet_point_1')->nullable();
            $table->text('bullet_point_2')->nullable();
            $table->text('bullet_point_3')->nullable();
            $table->text('bullet_point_4')->nullable();
            $table->text('bullet_point_5')->nullable();
            $table->text('description')->nullable();
            $table->text('search_terms')->nullable();
            $table->text('advertising_keywords')->nullable();
            $table->string('instructions_file')->nullable()->comment('Packaging & Certi');
            $table->string('country')->nullable();
            $table->string('product_category')->nullable();
            $table->integer('progress_status')->default(0);
            $table->integer('disc_status')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_listings');
    }
};
