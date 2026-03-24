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
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LiveAuctionController extends Controller
{
    // ── Compute increment for the NEXT bid given current bid count ──────────
    private function computeIncrement(int $playerBidCount): int
    {
        $type = Setting::get('bid_increment_type', 'tiered');

        if ($type === 'fixed') {
            return max(1, (int) Setting::get('bid_increment_fixed', 1000));
        }

        $tiers  = array_values(array_filter(
            array_map('intval', explode(',', Setting::get('bid_increment_tiers', '100,500,1000,2000')))
        ));
        if (empty($tiers)) return 100;

        $nBids     = max(1, (int) Setting::get('bid_tier_every_n_bids', 3));
        $tierIndex = min((int) floor($playerBidCount / $nBids), count($tiers) - 1);
        return $tiers[$tierIndex];
    }

    // ── Compute the exact amount the next bidder must send ──────────────────
    // When no bids placed yet → next_bid = bid_start_amount (opening price)
    // When bids exist         → next_bid = current_bid + increment
    private function computeNextBid(Auction $auction, $state): int
    {
        if (!$state->current_player_id) return 0;

        $playerBidCount = Bid::where('auction_id', $auction->id)
                             ->where('player_id', $state->current_player_id)
                             ->count();

        if ($playerBidCount === 0) {
            // No bids yet — opening bid is bid_start_amount
            $start = (int) Setting::get('bid_start_amount', 25);
            return $start > 0 ? $start : 25;
        }

        return (int) $state->current_bid + $this->computeIncrement($playerBidCount);
    }

    // ── Attach next_bid to state object ─────────────────────────────────────
    private function withNextBid(Auction $auction, $state): mixed
    {
        $state->next_bid = $this->computeNextBid($auction, $state);
        return $state;
    }

    // ── Routes ──────────────────────────────────────────────────────────────

    public function state(Auction $auction)
    {
        $state = $auction->liveState()->firstOrFail();
        $state->load(['currentPlayer', 'currentHighestBidder']);
        return new AuctionLiveStateResource($this->withNextBid($auction, $state));
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

        $this->withNextBid($auction, $state);
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

        $this->withNextBid($auction, $state);
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

        // Reset previous live player back to pending
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

        $state = $auction->liveState;
        $state->update([
            'current_player_id'         => $data['player_id'],
            'current_highest_bidder_id' => null,
            'current_bid'               => 0,   // reset — next_bid will be bid_start_amount
            'timer_seconds'             => $auction->bid_timer,
            'timer_started_at'          => now(),
        ]);
        $state->load(['currentPlayer', 'currentHighestBidder']);

        $this->withNextBid($auction, $state);
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

        // Compute expected next bid server-side
        $expectedAmount = $this->computeNextBid($auction, $state);

        if ($data['amount'] < $expectedAmount) {
            return response()->json([
                'message' => "Minimum bid is ₹{$expectedAmount}",
            ], 422);
        }

        $maxAmount = (int) Setting::get('bid_max_amount', 0);
        if ($maxAmount > 0 && $data['amount'] > $maxAmount) {
            return response()->json(['message' => "Bid cannot exceed ₹{$maxAmount}"], 422);
        }

        $auctionTeam = DB::table('auction_teams')
            ->where('auction_id', $auction->id)
            ->where('team_id', $data['team_id'])
            ->first();

        if (!$auctionTeam) {
            return response()->json(['message' => 'Team is not part of this auction'], 422);
        }

        if ($auctionTeam->budget_remaining < $data['amount']) {
            return response()->json(['message' => 'Insufficient budget'], 422);
        }

        if ((int) $state->current_highest_bidder_id === (int) $data['team_id']) {
            return response()->json(['message' => 'Your team is already the highest bidder'], 422);
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
        $state->refresh();
        $state->load(['currentPlayer', 'currentHighestBidder']);

        $this->withNextBid($auction, $state);
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
        $this->withNextBid($auction, $state);
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

        $this->withNextBid($auction, $state);
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
