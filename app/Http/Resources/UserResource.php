<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'email'      => $this->email,
            'role'       => $this->role,
            'team_id'    => $this->team_id,
            'player_id'  => $this->player_id,
            'team'       => new TeamResource($this->whenLoaded('team')),
            'player'     => new PlayerResource($this->whenLoaded('player')),
            'created_at' => $this->created_at,
        ];
    }
}
