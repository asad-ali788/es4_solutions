<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sourcing_container_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('sourcing_container_id')->constrained('sourcing_container')->onDelete('cascade');
            $table->foreignId('supplier_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('sku')->nullable();
            $table->string('ean')->nullable();
            $table->string('short_title')->nullable();
            $table->text('amazon_url')->nullable();
            $table->string('image')->nullable();
            $table->text('description')->nullable();
            $table->decimal('base_price_us', 10, 2)->nullable();
            $table->decimal('base_price_uk', 10, 2)->nullable();
            $table->decimal('base_price_eu', 10, 2)->nullable();
            $table->string('qty_to_order')->nullable();
            $table->string('qty_to_order_uk')->nullable();

            $table->text('notes')->nullable();
            $table->boolean('add_to_pl')->default(0);
            $table->string('asin_no')->nullable();
            $table->decimal('amz_price', 10, 2)->nullable();
            $table->string('suplier_price', 10)->nullable();

            $table->boolean('archived')->default(0);
            $table->text('archived_note')->nullable();
            $table->unsignedBigInteger('archiver_user_id')->nullable();
            $table->date('archived_date')->nullable();
        
            $table->json('fba_cost')->nullable();
            $table->decimal('carton_length', 10, 2)->nullable();
            $table->decimal('carton_width', 10, 2)->nullable();
            $table->decimal('carton_height', 10, 2)->nullable();
            $table->decimal('item_length', 10, 2)->nullable();
            $table->decimal('item_widht', 10, 2)->nullable();
            $table->decimal('item_height', 10, 2)->nullable();
            $table->integer('carton_qty')->nullable();
            $table->integer('pro_weight')->nullable();
            $table->decimal('shipping_usd', 10, 2)->nullable();
            
            $table->decimal('unit_price', 10, 2)->nullable();
            $table->decimal('shipping_cost', 10, 2)->nullable();
            $table->decimal('landed_costs_eu', 10, 2)->nullable();
            $table->decimal('landed_costs_us', 10, 2)->nullable();
            $table->decimal('landed_costs_uk', 10, 2)->nullable();
            $table->decimal('moq', 10, 2)->nullable();
            $table->decimal('total_order_value', 10, 2)->nullable();


            $table->text('pro_variations')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sourcing_container_items');
    }
};
