<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    protected $fillable = ['name', 'email', 'phone', 'short_name', 'color', 'logo'];

    public function users() { return $this->hasMany(User::class); }
    public function auctions() { return $this->belongsToMany(Auction::class, 'auction_teams')->withPivot('budget_remaining')->withTimestamps(); }
    public function bids() { return $this->hasMany(Bid::class); }
}
