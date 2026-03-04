<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sourcing_container', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('container_id');
            $table->text('descriptions')->nullable();
            $table->date('due_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sourcing_container');
    }
};

