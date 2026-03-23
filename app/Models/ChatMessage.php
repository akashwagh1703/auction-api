<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    protected $fillable = ['auction_id', 'user_id', 'message'];

    public function auction() { return $this->belongsTo(Auction::class); }
    public function user() { return $this->belongsTo(User::class); }
}
