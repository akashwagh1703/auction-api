<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BidResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'auction_id' => $this->auction_id,
            'player_id'  => $this->player_id,
            'team_id'    => $this->team_id,
            'user_id'    => $this->user_id,
            'amount'     => $this->amount,
            'next_bid'   => $this->next_bid ?? null,
            'team'       => new TeamResource($this->whenLoaded('team')),
            'player'     => new PlayerResource($this->whenLoaded('player')),
            'created_at' => $this->created_at,
        ];
    }
}
