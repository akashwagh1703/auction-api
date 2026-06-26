<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Auction;
use App\Models\Bid;
use App\Models\Player;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BiddingController extends Controller
{
    /**
     * Get current auction state (HTTP-based polling replacement for WebSocket)
     */
    public function getAuctionState(Request $request, $auctionId)
    {
        $auction = Auction::with(['players', 'teams', 'bids.team'])->findOrFail($auctionId);

        return response()->json([
            'auction' => [
                'id' => $auction->id,
                'name' => $auction->name,
                'status' => $auction->status,
                'current_player' => $auction->players->where('pivot.status', 'active')->first(),
                'rules' => [
                    'bid_timer' => $auction->bid_timer,
                    'bid_increment_type' => $auction->bid_increment_type,
                    'bid_increment_value' => $auction->bid_increment_value,
                    'min_bid_amount' => $auction->min_bid_amount,
                    'max_bid_amount' => $auction->max_bid_amount,
                    'timer_extension' => $auction->timer_extension,
                ],
            ],
            'teams' => $auction->teams->map(function ($team) use ($auction) {
                return [
                    'id' => $team->id,
                    'name' => $team->name,
                    'budget_remaining' => $team->pivot->budget_remaining,
                    'players_count' => $auction->bids->where('team_id', $team->id)->count(),
                ];
            }),
            'recent_bids' => $auction->bids()->with('team')->orderBy('created_at', 'desc')->limit(10)->get(),
        ]);
    }

    /**
     * Place a bid (HTTP-based replacement for WebSocket)
     */
    public function placeBid(Request $request, $auctionId, $playerId)
    {
        $request->validate([
            'amount' => 'required|integer|min:0',
        ]);

        $auction = Auction::findOrFail($auctionId);
        $player = $auction->players()->where('players.id', $playerId)->where('pivot.status', 'active')->firstOrFail();
        $team = $request->user()->team;

        if (!$team) {
            return response()->json(['message' => 'User is not assigned to a team'], 403);
        }

        // Check if auction is active
        if ($auction->status !== 'active') {
            return response()->json(['message' => 'Auction is not active'], 400);
        }

        // Check if team has enough budget
        $teamAuction = $auction->teams()->where('teams.id', $team->id)->first();
        if (!$teamAuction || $teamAuction->pivot->budget_remaining < $request->amount) {
            return response()->json(['message' => 'Insufficient budget'], 400);
        }

        // Check bid amount against rules
        $highestBid = $auction->bids()->where('player_id', $playerId)->max('amount') ?? $player->base_price;
        $minBid = $this->calculateMinBid($highestBid, $auction);

        if ($request->amount < $minBid) {
            return response()->json(['message' => "Bid must be at least {$minBid}"], 400);
        }

        if ($auction->max_bid_amount && $request->amount > $auction->max_bid_amount) {
            return response()->json(['message' => "Bid cannot exceed {$auction->max_bid_amount}"], 400);
        }

        // Place the bid
        DB::transaction(function () use ($auction, $player, $team, $request) {
            $bid = Bid::create([
                'auction_id' => $auction->id,
                'player_id' => $player->id,
                'team_id' => $team->id,
                'user_id' => $request->user()->id,
                'amount' => $request->amount,
            ]);

            // Update team budget
            $auction->teams()->updateExistingPivot($team->id, [
                'budget_remaining' => $team->pivot->budget_remaining - $request->amount
            ]);
        });

        return response()->json(['message' => 'Bid placed successfully']);
    }

    /**
     * Mark player as sold (admin only)
     */
    public function markSold(Request $request, $auctionId, $playerId)
    {
        $auction = Auction::findOrFail($auctionId);
        $player = $auction->players()->where('players.id', $playerId)->where('pivot.status', 'active')->firstOrFail();

        $request->validate([
            'team_id' => 'required|exists:teams,id',
            'price' => 'required|integer|min:0',
        ]);

        DB::transaction(function () use ($auction, $player, $request) {
            // Update player status
            $auction->players()->updateExistingPivot($player->id, [
                'status' => 'sold',
                'sold_to_team_id' => $request->team_id,
                'sold_price' => $request->price,
            ]);

            // Move to next player
            $nextPlayer = $auction->players()->where('pivot.status', 'pending')->first();
            if ($nextPlayer) {
                $auction->players()->updateExistingPivot($nextPlayer->id, ['status' => 'active']);
            }
        });

        return response()->json(['message' => 'Player marked as sold']);
    }

    /**
     * Mark player as unsold (admin only)
     */
    public function markUnsold(Request $request, $auctionId, $playerId)
    {
        $auction = Auction::findOrFail($auctionId);
        $player = $auction->players()->where('players.id', $playerId)->where('pivot.status', 'active')->firstOrFail();

        DB::transaction(function () use ($auction, $player) {
            $auction->players()->updateExistingPivot($player->id, ['status' => 'unsold']);

            // Move to next player
            $nextPlayer = $auction->players()->where('pivot.status', 'pending')->first();
            if ($nextPlayer) {
                $auction->players()->updateExistingPivot($nextPlayer->id, ['status' => 'active']);
            }
        });

        return response()->json(['message' => 'Player marked as unsold']);
    }

    /**
     * Move to next player (admin only)
     */
    public function nextPlayer(Request $request, $auctionId)
    {
        $auction = Auction::findOrFail($auctionId);

        $currentPlayer = $auction->players()->where('pivot.status', 'active')->first();
        if ($currentPlayer) {
            $auction->players()->updateExistingPivot($currentPlayer->id, ['status' => 'skipped']);
        }

        $nextPlayer = $auction->players()->where('pivot.status', 'pending')->first();
        if ($nextPlayer) {
            $auction->players()->updateExistingPivot($nextPlayer->id, ['status' => 'active']);
            return response()->json(['message' => 'Moved to next player', 'player' => $nextPlayer]);
        }

        // No more players, end auction
        $auction->update(['status' => 'completed']);
        return response()->json(['message' => 'Auction completed']);
    }

    /**
     * Calculate minimum bid based on auction rules
     */
    private function calculateMinBid($currentBid, $auction)
    {
        if ($auction->bid_increment_type === 'percentage') {
            return $currentBid + ($currentBid * $auction->bid_increment_value / 100);
        }

        return $currentBid + $auction->bid_increment_value;
    }
}
