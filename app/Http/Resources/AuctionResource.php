<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuctionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'name'                  => $this->name,
            'sport'                 => $this->sport,
            'date'                  => $this->date,
            'description'           => $this->description,
            'status'                => $this->status,
            'bid_timer'             => $this->bid_timer,
            'bid_increments'        => $this->bid_increments,
            'budget_per_team'       => $this->budget_per_team,
            'max_players_per_team'  => $this->max_players_per_team,
            'teams_count'           => $this->whenCounted('teams'),
            'players_count'         => $this->whenCounted('players'),
            'teams'                 => TeamResource::collection($this->whenLoaded('teams')),
            'players'               => PlayerResource::collection($this->whenLoaded('players')),
            'live_state'            => new AuctionLiveStateResource($this->whenLoaded('liveState')),
            'created_at'            => $this->created_at,
            'updated_at'            => $this->updated_at,
        ];
    }
}
