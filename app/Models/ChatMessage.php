<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class ChatMessage extends Model
{
    use SoftDeletes, Auditable;

    protected $fillable = ['auction_id', 'user_id', 'message'];

    public function auction() { return $this->belongsTo(Auction::class); }
    public function user() { return $this->belongsTo(User::class); }
}
