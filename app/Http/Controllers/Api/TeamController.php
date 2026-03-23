<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TeamResource;
use App\Imports\TeamsImport;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;

class TeamController extends Controller
{
    public function index()
    {
        return TeamResource::collection(Team::withCount('users')->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'       => 'required|string|max:100',
            'email'      => 'nullable|email|unique:teams,email|unique:users,email',
            'phone'      => 'nullable|string|max:20',
            'short_name' => 'required|string|max:10',
            'color'      => 'required|string|max:7',
            'logo'       => 'nullable|string',
        ]);

        $team = Team::create($data);

        // Auto-create owner login account if email provided
        if (!empty($data['email']) && !empty($data['phone'])) {
            User::create([
                'name'     => $team->name,
                'email'    => $data['email'],
                'password' => Hash::make($data['phone']),
                'role'     => 'owner',
                'team_id'  => $team->id,
            ]);
        }

        return new TeamResource($team);
    }

    public function show(Team $team)
    {
        return new TeamResource($team->load('users'));
    }

    public function update(Request $request, Team $team)
    {
        $data = $request->validate([
            'name'       => 'sometimes|string|max:100',
            'email'      => 'nullable|email|unique:teams,email,' . $team->id . '|unique:users,email,' . optional($team->users->first())->id . ',id',
            'phone'      => 'nullable|string|max:20',
            'short_name' => 'sometimes|string|max:10',
            'color'      => 'sometimes|string|max:7',
            'logo'       => 'nullable|string',
        ]);

        $team->update($data);

        // Sync owner user account
        $owner = $team->users()->where('role', 'owner')->first();
        if ($owner) {
            $userUpdate = [];
            if (!empty($data['name']))  $userUpdate['name']  = $data['name'];
            if (!empty($data['email'])) $userUpdate['email'] = $data['email'];
            if (!empty($data['phone'])) $userUpdate['password'] = Hash::make($data['phone']);
            if (!empty($userUpdate))    $owner->update($userUpdate);
        } elseif (!empty($data['email']) && !empty($data['phone'])) {
            User::create([
                'name'     => $team->name,
                'email'    => $data['email'],
                'password' => Hash::make($data['phone']),
                'role'     => 'owner',
                'team_id'  => $team->id,
            ]);
        }

        return new TeamResource($team);
    }

    public function destroy(Team $team)
    {
        $team->users()->delete();
        $team->delete();
        return response()->json(null, 204);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        Excel::import(new TeamsImport, $request->file('file'));

        return response()->json(['message' => 'Teams imported successfully']);
    }
}
