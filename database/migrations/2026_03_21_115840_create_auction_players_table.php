<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auction_players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('auction_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['pending', 'live', 'sold', 'unsold'])->default('pending');
            $table->foreignId('sold_to_team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->unsignedInteger('sold_price')->nullable();
            $table->timestamps();

            $table->unique(['auction_id', 'player_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auction_players');
    }
};
