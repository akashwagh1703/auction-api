<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Player extends Model
{
    protected $fillable = [
        'name',
        'category', // batsman, bowler, all-rounder, wicket-keeper
        'nationality',
        'age',
        'base_price',
        'image',
        'description'
    ];

    public function auctions()
    {
        return $this->belongsToMany(Auction::class, 'auction_players')
            ->withPivot('status', 'sold_to_team_id', 'sold_price')
            ->withTimestamps();
    }

    public function bids()
    {
        return $this->hasMany(Bid::class);
    }
}
