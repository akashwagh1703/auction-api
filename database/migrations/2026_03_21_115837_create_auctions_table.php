<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auctions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('status', ['draft', 'active', 'completed'])->default('draft');
            $table->unsignedSmallInteger('bid_timer')->default(30);
            $table->json('bid_increments')->nullable();
            $table->unsignedInteger('budget_per_team')->default(1000000);
            $table->unsignedSmallInteger('max_players_per_team')->default(11);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auctions');
    }
};
