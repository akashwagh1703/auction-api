<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Player extends Model
{
    protected $fillable = ['name', 'email', 'phone', 'role', 'nationality', 'age', 'base_price', 'rating', 'stats', 'image'];

    protected $casts = ['stats' => 'array'];

    public function user() { return $this->hasOne(User::class); }
    public function auctions() { return $this->belongsToMany(Auction::class, 'auction_players')->withPivot('status', 'sold_to_team_id', 'sold_price')->withTimestamps(); }
    public function bids() { return $this->hasMany(Bid::class); }
}
