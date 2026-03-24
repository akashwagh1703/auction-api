<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Auction extends Model
{
    protected $fillable = ['name', 'sport', 'date', 'description', 'status', 'bid_timer', 'bid_increments', 'budget_per_team', 'max_players_per_team'];

    protected $casts = ['bid_increments' => 'array'];

    public function teams() { return $this->belongsToMany(Team::class, 'auction_teams')->withPivot('budget_remaining')->withTimestamps(); }
    public function players() { return $this->belongsToMany(Player::class, 'auction_players')->withPivot('status', 'sold_to_team_id', 'sold_price')->withTimestamps(); }
    public function liveState() { return $this->hasOne(AuctionLiveState::class); }
    public function bids() { return $this->hasMany(Bid::class); }
    public function chatMessages() { return $this->hasMany(ChatMessage::class); }
}
