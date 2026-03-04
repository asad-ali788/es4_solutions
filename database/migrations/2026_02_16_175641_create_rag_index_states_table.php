<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('rag_index_states', function (Blueprint $table) {
            $table->id();
            $table->string('source', 120)->unique(); // e.g. amz_ads_keyword_performance_report
            $table->unsignedBigInteger('last_id')->default(0);
            $table->timestamp('last_updated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rag_index_states');
    }
};
