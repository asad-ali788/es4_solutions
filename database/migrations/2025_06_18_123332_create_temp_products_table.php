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
        Schema::create('temp_products', function (Blueprint $table) {
            $table->id();
            $table->string('item_name')->nullable();
            $table->text('item_description')->nullable();
            $table->string('listing_id')->nullable();
            $table->string('seller_sku')->nullable();
            $table->string('price')->nullable();
            $table->string('quantity')->nullable();
            $table->string('open_date')->nullable();
            $table->string('image_url')->nullable();
            $table->string('item_is_marketplace')->nullable();
            $table->string('product_id_type')->nullable();
            $table->string('zshop_shipping_fee')->nullable();
            $table->string('item_note')->nullable();
            $table->string('item_condition')->nullable();
            $table->string('zshop_category1')->nullable();
            $table->string('zshop_browse_path')->nullable();
            $table->string('zshop_storefront_feature')->nullable();
            $table->string('asin1')->nullable();
            $table->string('asin2')->nullable();
            $table->string('asin3')->nullable();
            $table->string('will_ship_internationally')->nullable();
            $table->string('expedited_shipping')->nullable();
            $table->string('zshop_boldface')->nullable();
            $table->string('product_id')->nullable();
            $table->string('bid_for_featured_placement')->nullable();
            $table->string('add_delete')->nullable();
            $table->string('pending_quantity')->nullable();
            $table->string('fulfillment_channel')->nullable();
            $table->string('merchant_shipping_group')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('temp_products');
    }
};
