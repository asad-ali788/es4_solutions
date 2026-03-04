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
        Schema::create('price_update_queues', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->nullable();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->string('feed_id')->nullable();
            $table->string('country', 10)->nullable();
            $table->string('currency', 10)->nullable();
            $table->decimal('new_price', 10, 2)->nullable();
            $table->decimal('old_price', 10, 2)->nullable();
            $table->decimal('base_price', 10, 2)->nullable();
            $table->enum('status', ['submitted', 'pending', 'success', 'failed'])->default('pending');
            $table->foreignId('pi_user_id')->constrained('users')->onDelete('cascade');
            $table->string('reference')->nullable();
            $table->foreignId('price_update_reason_id')->constrained('price_update_reasons')->onDelete('cascade');
            $table->timestamp('added_date')->useCurrent();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('price_update_queues');
    }
};
