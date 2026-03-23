<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PlayerResource;
use App\Imports\PlayersImport;
use App\Models\Player;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;

class PlayerController extends Controller
{
    public function index(Request $request)
    {
        $query = Player::query();

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('search')) {
            $query->where('name', 'ilike', '%' . $request->search . '%');
        }

        return PlayerResource::collection($query->orderBy('name')->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'email'       => 'nullable|email|unique:players,email|unique:users,email',
            'phone'       => 'nullable|string|max:20',
            'role'        => 'required|string|max:50',
            'nationality' => 'nullable|string|max:50',
            'age'         => 'nullable|integer|min:1|max:100',
            'base_price'  => 'required|integer|min:0',
            'rating'      => 'nullable|numeric|min:0|max:100',
            'stats'       => 'nullable|array',
            'image'       => 'nullable|string',
        ]);

        $player = Player::create($data);

        // Auto-create login account if email provided
        if (!empty($data['email']) && !empty($data['phone'])) {
            User::create([
                'name'      => $player->name,
                'email'     => $data['email'],
                'password'  => Hash::make($data['phone']),
                'role'      => 'player',
                'player_id' => $player->id,
            ]);
        }

        return (new PlayerResource($player))->response()->setStatusCode(201);
    }

    public function show(Player $player)
    {
        return new PlayerResource($player);
    }

    public function update(Request $request, Player $player)
    {
        $data = $request->validate([
            'name'        => 'sometimes|string|max:100',
            'email'       => 'nullable|email|unique:players,email,' . $player->id . '|unique:users,email,' . optional($player->user)->id . ',id',
            'phone'       => 'nullable|string|max:20',
            'role'        => 'sometimes|string|max:50',
            'nationality' => 'nullable|string|max:50',
            'age'         => 'nullable|integer|min:1|max:100',
            'base_price'  => 'sometimes|integer|min:0',
            'rating'      => 'nullable|numeric|min:0|max:100',
            'stats'       => 'nullable|array',
            'image'       => 'nullable|string',
        ]);

        $player->update($data);

        // Sync user account
        $user = $player->user;
        if ($user) {
            $userUpdate = [];
            if (!empty($data['name']))  $userUpdate['name']  = $data['name'];
            if (!empty($data['email'])) $userUpdate['email'] = $data['email'];
            if (!empty($data['phone'])) $userUpdate['password'] = Hash::make($data['phone']);
            if (!empty($userUpdate))    $user->update($userUpdate);
        } elseif (!empty($data['email']) && !empty($data['phone'])) {
            // Create account if it didn't exist before
            User::create([
                'name'      => $player->name,
                'email'     => $data['email'],
                'password'  => Hash::make($data['phone']),
                'role'      => 'player',
                'player_id' => $player->id,
            ]);
        }

        return new PlayerResource($player);
    }

    public function destroy(Player $player)
    {
        $player->user?->delete();
        $player->delete();
        return response()->json(null, 204);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        Excel::import(new PlayersImport, $request->file('file'));

        return response()->json(['message' => 'Players imported successfully']);
    }
}
