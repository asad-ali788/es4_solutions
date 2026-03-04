<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sourcing_buyer_QuestionChat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sourcing_container_items_id')->constrained('sourcing_container_items')->onDelete('cascade');
            $table->text('q_a')->nullable();
            $table->string('attachment')->nullable();
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('receiver_id')->constrained('users')->onDelete('cascade');
            $table->boolean('record_type')->nullable();
            $table->boolean('read_status')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sourcing_buyer_QuestionChat');
    }
};

