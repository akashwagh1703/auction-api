<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuctionLiveStateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                        => $this->id,
            'auction_id'                => $this->auction_id,
            'is_live'                   => $this->is_live,
            'current_player_id'         => $this->current_player_id,
            'current_highest_bidder_id' => $this->current_highest_bidder_id,
            'current_bid'               => $this->current_bid,
            'timer_seconds'             => $this->timer_seconds,
            'timer_started_at'          => $this->timer_started_at?->toISOString(),
            'current_player'            => new PlayerResource($this->whenLoaded('currentPlayer')),
            'current_highest_bidder'    => new TeamResource($this->whenLoaded('currentHighestBidder')),
        ];
    }
}
