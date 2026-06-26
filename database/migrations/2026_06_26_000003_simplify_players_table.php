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
        Schema::table('players', function (Blueprint $table) {
            // Drop old fields
            $table->dropColumn(['email', 'phone', 'role', 'rating', 'stats']);
            
            // Add new simplified fields
            $table->string('category')->default('batsman')->after('name'); // batsman, bowler, all-rounder, wicket-keeper
            $table->text('description')->nullable()->after('image');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            // Restore old fields
            $table->string('email')->nullable()->after('name');
            $table->string('phone')->nullable()->after('email');
            $table->string('role')->nullable()->after('phone');
            $table->integer('rating')->nullable()->after('role');
            $table->json('stats')->nullable()->after('rating');
            
            // Drop new fields
            $table->dropColumn(['category', 'description']);
        });
    }
};
