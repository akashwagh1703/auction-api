<?php

namespace Database\Seeders;

use App\Models\Auction;
use App\Models\AuctionLiveState;
use App\Models\Player;
use App\Models\Team;
use Illuminate\Database\Seeder;

class AuctionSeeder extends Seeder
{
    public function run(): void
    {
        $auction = Auction::create([
            'name'                => 'IPL Mega Auction 2025',
            'description'         => 'Annual IPL player auction',
            'status'              => 'draft',
            'bid_timer'           => 30,
            'bid_increments'      => [10000, 25000, 50000, 100000],
            'budget_per_team'     => 1000000,
            'max_players_per_team'=> 11,
        ]);

        // Attach all teams with full budget
        $teams = Team::all();
        foreach ($teams as $team) {
            $auction->teams()->attach($team->id, ['budget_remaining' => 1000000]);
        }

        // Attach all players as pending
        $players = Player::all();
        foreach ($players as $player) {
            $auction->players()->attach($player->id, ['status' => 'pending']);
        }

        // Create live state record
        AuctionLiveState::create([
            'auction_id'   => $auction->id,
            'is_live'      => false,
            'timer_seconds'=> 30,
        ]);
    }
}
