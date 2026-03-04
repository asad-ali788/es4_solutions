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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->string('notification_id')->nullable();
            $table->text('title')->nullable();
            $table->text('details')->nullable();
            $table->tinyInteger('level')->default(1);
            $table->tinyInteger('read_status')->default(0);
            $table->dateTime('created_date')->nullable();
            $table->dateTime('read_date')->nullable();
            $table->foreignId('handler')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
