<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlayerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'email'       => $this->email,
            'phone'       => $this->phone,
            'role'        => $this->role,
            'nationality' => $this->nationality,
            'age'         => $this->age,
            'base_price'  => $this->base_price,
            'rating'      => $this->rating,
            'stats'       => $this->stats,
            'image'       => $this->image,
            'pivot'       => $this->whenPivotLoaded('auction_players', fn() => [
                'status'          => $this->pivot->status,
                'sold_to_team_id' => $this->pivot->sold_to_team_id,
                'sold_price'      => $this->pivot->sold_price,
            ]),
            'created_at'  => $this->created_at,
        ];
    }
}
