<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class Bid extends Model
{
    use SoftDeletes, Auditable;

    protected $fillable = ['auction_id', 'player_id', 'team_id', 'user_id', 'amount'];

    public function auction() { return $this->belongsTo(Auction::class); }
    public function player() { return $this->belongsTo(Player::class); }
    public function team() { return $this->belongsTo(Team::class); }
    public function user() { return $this->belongsTo(User::class); }
}
