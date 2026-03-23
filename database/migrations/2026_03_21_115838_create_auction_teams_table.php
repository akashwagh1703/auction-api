<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auction_teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('auction_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('budget_remaining')->default(0);
            $table->timestamps();

            $table->unique(['auction_id', 'team_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auction_teams');
    }
};
