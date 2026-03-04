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
        Schema::create('product_additional_details', function (Blueprint $table) {
            $table->id();
            $table->string('fba_barcode_file')->nullable()->comment('Barcode Info');
            $table->string('product_label_file')->nullable()->comment('Barcode Info');
            $table->string('instructions_file_2')->nullable()->comment('Barcode Info');
            $table->text('listing_to_copy')->nullable();
            $table->string('listing_research_file')->nullable();
            $table->text('warnings')->nullable();

            $table->string('image1')->nullable();
            $table->string('image2')->nullable();
            $table->string('image3')->nullable();
            $table->string('image4')->nullable();
            $table->string('image5')->nullable();
            $table->string('image6')->nullable();
            // Foreign key relation
            $table->foreignId('product_listings_id')->constrained('product_listings')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_additional_details');
    }
};
