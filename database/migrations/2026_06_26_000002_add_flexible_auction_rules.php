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
        Schema::table('auctions', function (Blueprint $table) {
            // Flexible auction rules
            $table->integer('bid_timer')->default(30)->after('status');
            $table->enum('bid_increment_type', ['fixed', 'percentage'])->default('fixed')->after('bid_timer');
            $table->integer('bid_increment_value')->default(1000)->after('bid_increment_type');
            $table->json('bid_increment_thresholds')->nullable()->after('bid_increment_value');
            $table->integer('min_bid_amount')->default(0)->after('bid_increment_thresholds');
            $table->integer('max_bid_amount')->nullable()->after('min_bid_amount');
            $table->integer('timer_extension')->default(10)->after('max_bid_amount');
            $table->integer('budget_per_team')->default(100000)->after('timer_extension');
            $table->integer('max_players_per_team')->default(15)->after('budget_per_team');
            
            // Cricket-specific rules
            $table->integer('max_batsmen')->nullable()->after('max_players_per_team');
            $table->integer('max_bowlers')->nullable()->after('max_batsmen');
            $table->integer('max_all_rounders')->nullable()->after('max_bowlers');
            $table->integer('max_wicket_keepers')->nullable()->after('max_all_rounders');
            
            // Drop old fields that are no longer needed
            $table->dropColumn(['bid_increments']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('auctions', function (Blueprint $table) {
            // Drop new fields
            $table->dropColumn([
                'bid_timer',
                'bid_increment_type',
                'bid_increment_value',
                'bid_increment_thresholds',
                'min_bid_amount',
                'max_bid_amount',
                'timer_extension',
                'budget_per_team',
                'max_players_per_team',
                'max_batsmen',
                'max_bowlers',
                'max_all_rounders',
                'max_wicket_keepers'
            ]);
            
            // Restore old field
            $table->json('bid_increments')->nullable();
        });
    }
};
