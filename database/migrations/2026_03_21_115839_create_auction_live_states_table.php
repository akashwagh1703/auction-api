<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auction_live_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('auction_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('is_live')->default(false);
            $table->foreignId('current_player_id')->nullable()->constrained('players')->nullOnDelete();
            $table->foreignId('current_highest_bidder_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->unsignedInteger('current_bid')->default(0);
            $table->unsignedSmallInteger('timer_seconds')->default(30);
            $table->timestamp('timer_started_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auction_live_states');
    }
};
