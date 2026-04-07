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
        Schema::create('ai_tool_query_logs', function (Blueprint $table) {
            $table->id();
            
            // Tool execution info
            $table->string('tool_name'); // e.g., 'UnifiedPerformanceQuery'
            $table->string('trace_id')->nullable(); // UUID for tracing a single execution
            
            // Correlation context - helps find related queries
            $table->string('user_id')->nullable()->index();
            $table->string('chat_id')->nullable()->index();
            $table->string('conversation_id')->nullable()->index();
            $table->string('request_id')->nullable()->index();
            $table->string('session_id')->nullable()->index();
            
            // Query details
            $table->json('parameters'); // All input parameters
            $table->integer('result_count')->default(0); // Number of results returned
            $table->json('aggregates')->nullable(); // Aggregates if applicable
            $table->json('meta')->nullable(); // Query metadata
            $table->float('execution_time_ms')->default(0); // How long the query took
            
            // Status
            $table->boolean('success')->default(true);
            $table->text('error_message')->nullable(); // If failed, store error
            
            $table->string('query_hash'); // Hash of parameters for deduplication/finding duplicates
            
            // Timestamps
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
            
            // Indexes for fast querying
            $table->index('tool_name');
            $table->index('created_at');
            $table->index(['tool_name', 'created_at']);
            $table->index(['tool_name', 'success']);
            $table->index('query_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_tool_query_logs');
    }
};
