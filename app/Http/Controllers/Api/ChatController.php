<?php

namespace App\Http\Controllers\Api;

use App\Events\ChatMessageSent;
use App\Http\Controllers\Controller;
use App\Http\Resources\ChatMessageResource;
use App\Models\Auction;
use App\Models\ChatMessage;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function index(Auction $auction)
    {
        $messages = $auction->chatMessages()
            ->with('user:id,name,role,team_id')
            ->orderBy('created_at')
            ->paginate(50);

        return ChatMessageResource::collection($messages);
    }

    public function store(Request $request, Auction $auction)
    {
        $data = $request->validate([
            'message' => 'required|string|max:500',
        ]);

        $chatMessage = ChatMessage::create([
            'auction_id' => $auction->id,
            'user_id'    => $request->user()->id,
            'message'    => $data['message'],
        ]);

        $chatMessage->load('user:id,name,role,team_id');

        broadcast(new ChatMessageSent($chatMessage));

        return (new ChatMessageResource($chatMessage))
            ->response()
            ->setStatusCode(201);
    }
}
