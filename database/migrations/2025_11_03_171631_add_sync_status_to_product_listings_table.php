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
        Schema::table('product_listings', function (Blueprint $table) {
            $table->enum('sync_status', ['clean', 'dirty', 'synced', 'failed'])
                ->default('clean')
                ->after('product_category');
            $table->boolean('title_change_status')->default(false);
            $table->boolean('bullets_change_status')->default(false);
            $table->boolean('description_change_status')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_listings', function (Blueprint $table) {
            $table->dropColumn(['sync_status', 'content_updated_at', 'synced_at']);
        });
    }
};
