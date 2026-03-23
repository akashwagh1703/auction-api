<?php

namespace App\Events;

use App\Models\Bid;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BidPlaced implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Bid $bid) {}

    public function broadcastOn(): array
    {
        return [new Channel("auction.{$this->bid->auction_id}")];
    }

    public function broadcastAs(): string
    {
        return 'bid.placed';
    }

    public function broadcastWith(): array
    {
        return [
            'id'        => $this->bid->id,
            'player_id' => $this->bid->player_id,
            'team_id'   => $this->bid->team_id,
            'amount'    => $this->bid->amount,
            'team'      => $this->bid->team,
            'created_at'=> $this->bid->created_at->toISOString(),
        ];
    }
}
