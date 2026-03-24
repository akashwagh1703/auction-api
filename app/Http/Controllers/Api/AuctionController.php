<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\LiveAuctionController;
use App\Http\Resources\AuctionResource;
use App\Models\Auction;
use App\Models\AuctionLiveState;
use Illuminate\Http\Request;

class AuctionController extends Controller
{
    public function index()
    {
        $auctions = Auction::with(['liveState', 'teams', 'players'])
            ->withCount(['teams', 'players'])
            ->orderByDesc('created_at')
            ->get();

        $liveController = new LiveAuctionController;
        foreach ($auctions as $auction) {
            if ($auction->liveState) {
                $liveController->computeAndAttachNextBid($auction, $auction->liveState);
            }
        }

        return AuctionResource::collection($auctions);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'                 => 'required|string|max:150',
            'sport'                => 'nullable|string|max:50',
            'date'                 => 'nullable|date',
            'description'          => 'nullable|string',
            'bid_timer'            => 'required|integer|min:10|max:120',
            'bid_increments'       => 'required|array|min:1',
            'bid_increments.*'     => 'integer|min:1',
            'budget_per_team'      => 'required|integer|min:1',
            'max_players_per_team' => 'required|integer|min:1',
            'team_ids'             => 'nullable|array',
            'team_ids.*'           => 'integer|exists:teams,id',
            'player_ids'           => 'nullable|array',
            'player_ids.*'         => 'integer|exists:players,id',
        ]);

        $auction = Auction::create([
            'name'                 => $data['name'],
            'sport'                => $data['sport'] ?? null,
            'date'                 => $data['date'] ?? null,
            'description'          => $data['description'] ?? null,
            'status'               => 'draft',
            'bid_timer'            => $data['bid_timer'],
            'bid_increments'       => $data['bid_increments'],
            'budget_per_team'      => $data['budget_per_team'],
            'max_players_per_team' => $data['max_players_per_team'],
        ]);

        if (!empty($data['team_ids'])) {
            $pivot = array_fill_keys($data['team_ids'], ['budget_remaining' => $data['budget_per_team']]);
            $auction->teams()->attach($pivot);
        }

        if (!empty($data['player_ids'])) {
            $pivot = array_fill_keys($data['player_ids'], ['status' => 'pending']);
            $auction->players()->attach($pivot);
        }

        AuctionLiveState::create([
            'auction_id'    => $auction->id,
            'is_live'       => false,
            'timer_seconds' => $data['bid_timer'],
        ]);

        return (new AuctionResource($auction->load(['teams', 'players', 'liveState'])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Auction $auction)
    {
        $auction->load(['teams', 'players', 'liveState']);
        if ($auction->liveState) {
            (new LiveAuctionController)->computeAndAttachNextBid($auction, $auction->liveState);
        }
        return new AuctionResource($auction);
    }

    public function update(Request $request, Auction $auction)
    {
        $data = $request->validate([
            'name'                 => 'sometimes|string|max:150',
            'sport'                => 'nullable|string|max:50',
            'date'                 => 'nullable|date',
            'description'          => 'nullable|string',
            'status'               => 'sometimes|in:draft,active,completed',
            'bid_timer'            => 'sometimes|integer|min:10|max:120',
            'bid_increments'       => 'sometimes|array|min:1',
            'bid_increments.*'     => 'integer|min:1',
            'budget_per_team'      => 'sometimes|integer|min:1',
            'max_players_per_team' => 'sometimes|integer|min:1',
        ]);

        $auction->update($data);
        $auction->load(['teams', 'players', 'liveState']);
        if ($auction->liveState) {
            (new LiveAuctionController)->computeAndAttachNextBid($auction, $auction->liveState);
        }
        return new AuctionResource($auction);
    }

    public function destroy(Auction $auction)
    {
        $auction->delete();

        return response()->json(null, 204);
    }

    public function attachTeams(Request $request, Auction $auction)
    {
        $data = $request->validate([
            'team_ids'   => 'required|array|min:1',
            'team_ids.*' => 'integer|exists:teams,id',
        ]);

        $pivot = array_fill_keys($data['team_ids'], ['budget_remaining' => $auction->budget_per_team]);
        $auction->teams()->sync($pivot);

        return new AuctionResource($auction->load('teams'));
    }

    public function attachPlayers(Request $request, Auction $auction)
    {
        $data = $request->validate([
            'player_ids'   => 'required|array|min:1',
            'player_ids.*' => 'integer|exists:players,id',
        ]);

        $pivot = array_fill_keys($data['player_ids'], ['status' => 'pending']);
        $auction->players()->sync($pivot);

        return new AuctionResource($auction->load('players'));
    }
}
