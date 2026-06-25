<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Bids table indexes
        Schema::table('bids', function (Blueprint $table) {
            $table->index(['auction_id', 'player_id', 'team_id'], 'bid_auction_player_team_idx');
            $table->index('amount', 'bid_amount_idx');
            $table->index('created_at', 'bid_created_at_idx');
        });

        // Auction live states indexes
        Schema::table('auction_live_states', function (Blueprint $table) {
            $table->index('is_live', 'live_state_is_live_idx');
            $table->index('current_player_id', 'live_state_current_player_idx');
            $table->index('current_highest_bidder_id', 'live_state_highest_bidder_idx');
        });

        // Chat messages indexes
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->index(['auction_id', 'created_at'], 'chat_auction_created_idx');
            $table->index('user_id', 'chat_user_idx');
        });

        // Players table indexes
        Schema::table('players', function (Blueprint $table) {
            $table->index('role', 'player_role_idx');
            $table->index('rating', 'player_rating_idx');
            $table->index('base_price', 'player_base_price_idx');
        });

        // Teams table indexes
        Schema::table('teams', function (Blueprint $table) {
            $table->index('name', 'team_name_idx');
            $table->index('short_name', 'team_short_name_idx');
        });

        // Users table indexes
        Schema::table('users', function (Blueprint $table) {
            $table->index('role', 'user_role_idx');
            $table->index('email', 'user_email_idx');
        });

        // Auctions table indexes
        Schema::table('auctions', function (Blueprint $table) {
            $table->index('status', 'auction_status_idx');
            $table->index('date', 'auction_date_idx');
        });

        // Audit logs indexes
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->index(['model_type', 'model_id'], 'audit_model_idx');
            $table->index('action', 'audit_action_idx');
            $table->index('created_at', 'audit_created_at_idx');
        });
    }

    public function down(): void
    {
        Schema::table('bids', function (Blueprint $table) {
            $table->dropIndex('bid_auction_player_team_idx');
            $table->dropIndex('bid_amount_idx');
            $table->dropIndex('bid_created_at_idx');
        });

        Schema::table('auction_live_states', function (Blueprint $table) {
            $table->dropIndex('live_state_is_live_idx');
            $table->dropIndex('live_state_current_player_idx');
            $table->dropIndex('live_state_highest_bidder_idx');
        });

        Schema::table('chat_messages', function (Blueprint $table) {
            $table->dropIndex('chat_auction_created_idx');
            $table->dropIndex('chat_user_idx');
        });

        Schema::table('players', function (Blueprint $table) {
            $table->dropIndex('player_role_idx');
            $table->dropIndex('player_rating_idx');
            $table->dropIndex('player_base_price_idx');
        });

        Schema::table('teams', function (Blueprint $table) {
            $table->dropIndex('team_name_idx');
            $table->dropIndex('team_short_name_idx');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('user_role_idx');
            $table->dropIndex('user_email_idx');
        });

        Schema::table('auctions', function (Blueprint $table) {
            $table->dropIndex('auction_status_idx');
            $table->dropIndex('auction_date_idx');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex('audit_model_idx');
            $table->dropIndex('audit_action_idx');
            $table->dropIndex('audit_created_at_idx');
        });
    }
};
