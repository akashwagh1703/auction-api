<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatMessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'auction_id' => $this->auction_id,
            'message'    => $this->message,
            'user'       => new UserResource($this->whenLoaded('user')),
            'created_at' => $this->created_at,
        ];
    }
}
