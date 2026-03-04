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
        Schema::create('temp_sales_orders', function (Blueprint $table) {
            $table->id();
            $table->string('amazon_order_id')->index();
            $table->string('merchant_order_id')->nullable();
            $table->timestamp('purchase_date')->nullable();
            $table->timestamp('last_updated_date')->nullable();
            $table->string('order_status')->nullable();
            $table->string('fulfillment_channel')->nullable();
            $table->string('sales_channel')->nullable();
            $table->string('order_channel')->nullable();
            $table->string('ship_service_level')->nullable();
            $table->text('product_name')->nullable();
            $table->string('sku')->nullable();
            $table->string('asin')->nullable();
            $table->string('item_status')->nullable();
            $table->integer('quantity')->nullable();
            $table->string('currency')->nullable();
            $table->decimal('item_price', 10, 2)->nullable();
            $table->decimal('item_tax', 10, 2)->nullable();
            $table->decimal('shipping_price', 10, 2)->nullable();
            $table->decimal('shipping_tax', 10, 2)->nullable();
            $table->decimal('gift_wrap_price', 10, 2)->nullable();
            $table->decimal('gift_wrap_tax', 10, 2)->nullable();
            $table->decimal('item_promotion_discount', 10, 2)->nullable();
            $table->decimal('ship_promotion_discount', 10, 2)->nullable();
            $table->string('ship_city')->nullable();
            $table->string('ship_state')->nullable();
            $table->string('ship_postal_code')->nullable();
            $table->string('ship_country')->nullable();
            $table->string('promotion_ids')->nullable();
            $table->string('cpf')->nullable();
            $table->boolean('is_business_order')->default(false);
            $table->string('purchase_order_number')->nullable();
            $table->string('price_designation')->nullable();
            $table->boolean('signature_confirmation_recommended')->default(false);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('temp_sales_orders');
    }
};
