<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ResetController extends Controller
{
    public function reset(Request $request)
    {
        DB::transaction(function () use ($request) {
            // Pivot + dependent tables first
            DB::table('chat_messages')->truncate();
            DB::table('bids')->truncate();
            DB::table('auction_live_states')->truncate();
            DB::table('auction_players')->truncate();
            DB::table('auction_teams')->truncate();
            DB::table('auctions')->truncate();
            DB::table('players')->truncate();
            // Remove non-admin users
            DB::table('personal_access_tokens')->truncate();
            DB::table('users')->where('role', '!=', 'admin')->delete();
            DB::table('teams')->truncate();
        });

        return response()->json(['message' => 'All data reset successfully']);
    }
}
