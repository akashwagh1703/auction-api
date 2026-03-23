<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuctionLiveState extends Model
{
    protected $fillable = [
        'auction_id', 'is_live', 'current_player_id',
        'current_highest_bidder_id', 'current_bid', 'timer_seconds', 'timer_started_at',
    ];

    protected $casts = ['is_live' => 'boolean', 'timer_started_at' => 'datetime'];

    public function auction() { return $this->belongsTo(Auction::class); }
    public function currentPlayer() { return $this->belongsTo(Player::class, 'current_player_id'); }
    public function currentHighestBidder() { return $this->belongsTo(Team::class, 'current_highest_bidder_id'); }
}
