<?php

namespace App\Http\Controllers\Api;

use App\Events\AuctionStateUpdated;
use App\Events\BidPlaced;
use App\Http\Controllers\Controller;
use App\Http\Resources\AuctionLiveStateResource;
use App\Http\Resources\BidResource;
use App\Models\Auction;
use App\Models\Bid;
use App\Models\Player;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LiveAuctionController extends Controller
{
    public function state(Auction $auction)
    {
        $state = $auction->liveState()->with(['currentPlayer', 'currentHighestBidder'])->firstOrFail();

        return new AuctionLiveStateResource($state);
    }

    public function start(Auction $auction)
    {
        if ($auction->status === 'completed') {
            return response()->json(['message' => 'Auction is already completed'], 422);
        }

        $auction->update(['status' => 'active']);

        $state = $auction->liveState;
        $state->update(['is_live' => true]);
        $state->load(['currentPlayer', 'currentHighestBidder']);

        broadcast(new AuctionStateUpdated($state));

        return new AuctionLiveStateResource($state);
    }

    public function stop(Auction $auction)
    {
        $state = $auction->liveState;
        $state->update([
            'is_live'                   => false,
            'current_player_id'         => null,
            'current_highest_bidder_id' => null,
            'current_bid'               => 0,
            'timer_started_at'          => null,
        ]);
        $state->load(['currentPlayer', 'currentHighestBidder']);

        broadcast(new AuctionStateUpdated($state));

        return new AuctionLiveStateResource($state);
    }

    public function nextPlayer(Request $request, Auction $auction)
    {
        $data = $request->validate([
            'player_id' => 'required|integer|exists:players,id',
        ]);

        $auctionPlayer = DB::table('auction_players')
            ->where('auction_id', $auction->id)
            ->where('player_id', $data['player_id'])
            ->where('status', 'pending')
            ->first();

        if (!$auctionPlayer) {
            return response()->json(['message' => 'Player not available for this auction'], 422);
        }

        if ($auction->liveState->current_player_id) {
            DB::table('auction_players')
                ->where('auction_id', $auction->id)
                ->where('player_id', $auction->liveState->current_player_id)
                ->where('status', 'live')
                ->update(['status' => 'pending']);
        }

        DB::table('auction_players')
            ->where('auction_id', $auction->id)
            ->where('player_id', $data['player_id'])
            ->update(['status' => 'live']);

        $player = Player::find($data['player_id']);

        $state = $auction->liveState;
        $state->update([
            'current_player_id'         => $data['player_id'],
            'current_highest_bidder_id' => null,
            'current_bid'               => $player->base_price,
            'timer_seconds'             => $auction->bid_timer,
            'timer_started_at'          => now(),
        ]);
        $state->load(['currentPlayer', 'currentHighestBidder']);

        broadcast(new AuctionStateUpdated($state));

        return new AuctionLiveStateResource($state);
    }

    public function placeBid(Request $request, Auction $auction)
    {
        $data = $request->validate([
            'team_id' => 'required|integer|exists:teams,id',
            'amount'  => 'required|integer|min:1',
        ]);

        $state = $auction->liveState;

        if (!$state->is_live || !$state->current_player_id) {
            return response()->json(['message' => 'No active player up for bid'], 422);
        }

        if ($data['amount'] <= $state->current_bid) {
            return response()->json(['message' => 'Bid must be higher than current bid of ' . $state->current_bid], 422);
        }

        $auctionTeam = DB::table('auction_teams')
            ->where('auction_id', $auction->id)
            ->where('team_id', $data['team_id'])
            ->first();

        if (!$auctionTeam || $auctionTeam->budget_remaining < $data['amount']) {
            return response()->json(['message' => 'Insufficient budget'], 422);
        }

        $bid = Bid::create([
            'auction_id' => $auction->id,
            'player_id'  => $state->current_player_id,
            'team_id'    => $data['team_id'],
            'user_id'    => $request->user()->id,
            'amount'     => $data['amount'],
        ]);

        $bid->load('team');

        $state->update([
            'current_highest_bidder_id' => $data['team_id'],
            'current_bid'               => $data['amount'],
            'timer_seconds'             => $auction->bid_timer,
            'timer_started_at'          => now(),
        ]);
        $state->load(['currentPlayer', 'currentHighestBidder']);

        broadcast(new BidPlaced($bid));
        broadcast(new AuctionStateUpdated($state));

        return new BidResource($bid);
    }

    public function soldPlayer(Auction $auction)
    {
        $state = $auction->liveState;

        if (!$state->current_player_id || !$state->current_highest_bidder_id) {
            return response()->json(['message' => 'No winning bid to confirm'], 422);
        }

        DB::transaction(function () use ($auction, $state) {
            DB::table('auction_players')
                ->where('auction_id', $auction->id)
                ->where('player_id', $state->current_player_id)
                ->update([
                    'status'          => 'sold',
                    'sold_to_team_id' => $state->current_highest_bidder_id,
                    'sold_price'      => $state->current_bid,
                ]);

            DB::table('auction_teams')
                ->where('auction_id', $auction->id)
                ->where('team_id', $state->current_highest_bidder_id)
                ->decrement('budget_remaining', $state->current_bid);

            $state->update([
                'current_player_id'         => null,
                'current_highest_bidder_id' => null,
                'current_bid'               => 0,
                'timer_started_at'          => null,
            ]);
        });

        $state->load(['currentPlayer', 'currentHighestBidder']);
        broadcast(new AuctionStateUpdated($state));

        return new AuctionLiveStateResource($state);
    }

    public function markUnsold(Auction $auction)
    {
        $state = $auction->liveState;

        if (!$state->current_player_id) {
            return response()->json(['message' => 'No current player'], 422);
        }

        DB::table('auction_players')
            ->where('auction_id', $auction->id)
            ->where('player_id', $state->current_player_id)
            ->update(['status' => 'unsold']);

        $state->update([
            'current_player_id'         => null,
            'current_highest_bidder_id' => null,
            'current_bid'               => 0,
            'timer_started_at'          => null,
        ]);
        $state->load(['currentPlayer', 'currentHighestBidder']);

        broadcast(new AuctionStateUpdated($state));

        return new AuctionLiveStateResource($state);
    }

    public function bids(Auction $auction)
    {
        $bids = $auction->bids()
            ->with(['team', 'player'])
            ->orderByDesc('created_at')
            ->get();

        return BidResource::collection($bids);
    }
}
