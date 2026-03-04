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
        Schema::table('agent_conversation_messages', function (Blueprint $table) {
            // Change text columns to longText to support larger responses (HTML tables, etc.)
            $table->longText('content')->change();
            $table->longText('attachments')->nullable()->change();
            $table->longText('tool_calls')->nullable()->change();
            $table->longText('tool_results')->nullable()->change();
            $table->longText('usage')->nullable()->change();
            $table->longText('meta')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agent_conversation_messages', function (Blueprint $table) {
            // Revert to text columns
            $table->text('content')->change();
            $table->text('attachments')->nullable()->change();
            $table->text('tool_calls')->nullable()->change();
            $table->text('tool_results')->nullable()->change();
            $table->text('usage')->nullable()->change();
            $table->text('meta')->nullable()->change();
        });
    }
};
