<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeamResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'email'       => $this->email,
            'phone'       => $this->phone,
            'short_name'  => $this->short_name,
            'color'       => $this->color,
            'logo'        => $this->logo,
            'users_count' => $this->whenCounted('users'),
            'users'       => UserResource::collection($this->whenLoaded('users')),
            'pivot'       => $this->whenPivotLoaded('auction_teams', fn() => [
                'budget_remaining' => $this->pivot->budget_remaining,
            ]),
            'created_at'  => $this->created_at,
        ];
    }
}
