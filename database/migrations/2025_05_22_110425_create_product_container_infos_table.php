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
        Schema::create('product_container_infos', function (Blueprint $table) {
            $table->id();
            $table->string('commercial_invoice_title')->nullable();
            $table->string('hs_code')->nullable();
            $table->string('hs_code_percentage')->nullable();
            $table->string('item_size_length_cm')->nullable(); 
            $table->string('item_size_width_cm')->nullable();  
            $table->string('item_size_height_cm')->nullable(); 
            $table->string('ctn_size_length_cm')->nullable();  
            $table->string('ctn_size_width_cm')->nullable();   
            $table->string('ctn_size_height_cm')->nullable();  
            $table->decimal('item_weight_kg', 8, 2)->nullable();
            $table->decimal('carton_weight_kg', 8, 2)->nullable();
            $table->integer('quantity_per_carton')->nullable();
            $table->decimal('carton_cbm', 10, 4)->nullable();
            $table->integer('moq')->nullable();
            $table->string('product_material')->nullable();
            $table->integer('order_lead_time_weeks')->nullable();

            // Foreign key referencing product_listings table
            $table->foreignId('product_listings_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_container_infos');
    }
};
