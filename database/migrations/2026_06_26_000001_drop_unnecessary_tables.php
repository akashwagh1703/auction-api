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
        // Drop chat-related tables
        Schema::dropIfExists('chat_messages');

        // Drop live auction state table
        Schema::dropIfExists('auction_live_states');

        // Drop cache table (using file cache instead)
        Schema::dropIfExists('cache');

        // Drop jobs table (using sync queue instead)
        Schema::dropIfExists('jobs');

        // Drop job_batches table
        Schema::dropIfExists('job_batches');

        // Drop failed_jobs table
        Schema::dropIfExists('failed_jobs');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate tables if needed (not recommended for production)
    }
};
