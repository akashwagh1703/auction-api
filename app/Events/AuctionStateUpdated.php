<?php

namespace App\Events;

use App\Models\AuctionLiveState;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AuctionStateUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public AuctionLiveState $state) {}

    public function broadcastOn(): array
    {
        return [new Channel("auction.{$this->state->auction_id}")];
    }

    public function broadcastAs(): string
    {
        return 'state.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'is_live'                  => (bool) $this->state->is_live,
            'current_player_id'        => $this->state->current_player_id ? (int) $this->state->current_player_id : null,
            'current_highest_bidder_id'=> $this->state->current_highest_bidder_id ? (int) $this->state->current_highest_bidder_id : null,
            'current_bid'              => (int) $this->state->current_bid,
            'timer_seconds'            => (int) $this->state->timer_seconds,
            'timer_started_at'         => $this->state->timer_started_at?->toISOString(),
            'current_player'           => $this->state->currentPlayer,
            'current_highest_bidder'   => $this->state->currentHighestBidder,
        ];
    }
}
