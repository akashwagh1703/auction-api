<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        return UserResource::collection(
            User::with(['team:id,name,short_name,color', 'player:id,name,role'])
                ->orderBy('role')
                ->orderBy('name')
                ->get()
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:100',
            'email'     => 'required|email|unique:users,email',
            'password'  => 'required|string|min:6',
            'role'      => 'required|in:admin,owner,player',
            'team_id'   => 'nullable|integer|exists:teams,id',
            'player_id' => 'nullable|integer|exists:players,id',
        ]);

        $this->validateRoleAssignment($data);

        $user = User::create([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'password'  => Hash::make($data['password']),
            'role'      => $data['role'],
            'team_id'   => $data['team_id'] ?? null,
            'player_id' => $data['player_id'] ?? null,
        ]);

        return (new UserResource($user->load(['team:id,name,short_name,color', 'player:id,name,role'])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(User $user)
    {
        return new UserResource($user->load(['team:id,name,short_name,color', 'player:id,name,role']));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'      => 'sometimes|string|max:100',
            'email'     => ['sometimes', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'password'  => 'sometimes|string|min:6',
            'role'      => 'sometimes|in:admin,owner,player',
            'team_id'   => 'nullable|integer|exists:teams,id',
            'player_id' => 'nullable|integer|exists:players,id',
        ]);

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);

        return new UserResource($user->load(['team:id,name,short_name,color', 'player:id,name,role']));
    }

    public function destroy(User $user)
    {
        if ($user->id === request()->user()->id) {
            return response()->json(['message' => 'Cannot delete your own account'], 422);
        }

        $user->tokens()->delete();
        $user->delete();

        return response()->json(null, 204);
    }

    private function validateRoleAssignment(array $data): void
    {
        if ($data['role'] === 'owner' && empty($data['team_id'])) {
            abort(422, 'Owner role requires a team_id');
        }

        if ($data['role'] === 'player' && empty($data['player_id'])) {
            abort(422, 'Player role requires a player_id');
        }
    }
}
