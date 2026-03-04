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
        Schema::create('purchase_order_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->onDelete('cascade');
            $table->foreignId('changed_by')->nullable()->constrained('users')->onDelete('cascade');
            $table->enum('previous_status', ['draft', 'confirmed', 'shipped', 'received', 'cancelled'])->nullable();
            $table->enum('new_status', ['draft', 'confirmed', 'shipped', 'received', 'cancelled']);
            $table->text('change_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_order_logs');
    }
};
