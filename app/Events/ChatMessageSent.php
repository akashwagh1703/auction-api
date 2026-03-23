<?php

namespace App\Events;

use App\Models\ChatMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ChatMessage $chatMessage) {}

    public function broadcastOn(): array
    {
        return [new Channel("auction.{$this->chatMessage->auction_id}")];
    }

    public function broadcastAs(): string
    {
        return 'chat.message';
    }

    public function broadcastWith(): array
    {
        return [
            'id'         => $this->chatMessage->id,
            'auction_id' => $this->chatMessage->auction_id,
            'message'    => $this->chatMessage->message,
            'created_at' => $this->chatMessage->created_at->toISOString(),
            'user'       => [
                'id'      => $this->chatMessage->user->id,
                'name'    => $this->chatMessage->user->name,
                'role'    => $this->chatMessage->user->role,
                'team_id' => $this->chatMessage->user->team_id,
            ],
        ];
    }
}
