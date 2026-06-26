<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class Auction extends Model
{
    use SoftDeletes, Auditable;

    protected $fillable = [
        'name',
        'sport',
        'date',
        'description',
        'status',
        // Flexible auction rules
        'bid_timer', // Default bid timer in seconds
        'bid_increment_type', // 'fixed' or 'percentage'
        'bid_increment_value', // Fixed amount or percentage
        'bid_increment_thresholds', // JSON array of thresholds for dynamic increments
        'min_bid_amount', // Minimum bid amount
        'max_bid_amount', // Maximum bid amount
        'timer_extension', // Timer extension on last bid in seconds
        'budget_per_team', // Default budget per team
        'max_players_per_team', // Maximum players per team
        // Cricket-specific rules
        'max_batsmen', // Maximum batsmen per team
        'max_bowlers', // Maximum bowlers per team
        'max_all_rounders', // Maximum all-rounders per team
        'max_wicket_keepers', // Maximum wicket-keepers per team
    ];

    protected $casts = [
        'bid_increment_thresholds' => 'array',
        'date' => 'datetime',
    ];

    public function teams()
    {
        return $this->belongsToMany(Team::class, 'auction_teams')
            ->withPivot('budget_remaining')
            ->withTimestamps();
    }

    public function players()
    {
        return $this->belongsToMany(Player::class, 'auction_players')
            ->withPivot('status', 'sold_to_team_id', 'sold_price')
            ->withTimestamps();
    }

    public function bids()
    {
        return $this->hasMany(Bid::class);
    }
}
