<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop index first; MySQL does not support indexing full LONGTEXT without prefix.
        try {
            DB::statement('ALTER TABLE ai_tool_query_logs DROP INDEX ai_tool_query_logs_query_hash_index');
        } catch (\Throwable $e) {
            // Index may already be removed; ignore.
        }

        DB::statement('ALTER TABLE ai_tool_query_logs MODIFY query_hash LONGTEXT NOT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Truncate existing values so they fit varchar(255) on rollback.
        DB::statement('UPDATE ai_tool_query_logs SET query_hash = LEFT(query_hash, 255)');
        DB::statement('ALTER TABLE ai_tool_query_logs MODIFY query_hash VARCHAR(255) NOT NULL');

        try {
            DB::statement('ALTER TABLE ai_tool_query_logs ADD INDEX ai_tool_query_logs_query_hash_index (query_hash)');
        } catch (\Throwable $e) {
            // Ignore if index already exists.
        }
    }
};
