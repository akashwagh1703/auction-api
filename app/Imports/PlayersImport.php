<?php

namespace App\Imports;

use App\Models\Player;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class PlayersImport implements ToModel, WithHeadingRow, WithValidation
{
    public function model(array $row): Player
    {
        $player = new Player([
            'name'        => $row['name'],
            'email'       => $row['email'] ?? null,
            'phone'       => $row['phone'] ?? null,
            'role'        => $row['role'],
            'nationality' => $row['nationality'] ?? null,
            'age'         => $row['age'] ?? null,
            'base_price'  => $row['base_price'] ?? $row['baseprice'] ?? 0,
            'rating'      => $row['rating'] ?? null,
        ]);

        $player->save();

        // Auto-create login account
        $email = $row['email'] ?? null;
        $phone = $row['phone'] ?? null;
        if ($email && $phone && !User::where('email', $email)->exists()) {
            User::create([
                'name'      => $row['name'],
                'email'     => $email,
                'password'  => Hash::make($phone),
                'role'      => 'player',
                'player_id' => $player->id,
            ]);
        }

        return $player;
    }

    public function rules(): array
    {
        return [
            'name'       => 'required|string',
            'role'       => 'required|string',
            'base_price' => 'nullable|numeric|min:0',
            'age'        => 'nullable|integer|min:1|max:100',
            'rating'     => 'nullable|numeric|min:0|max:100',
        ];
    }
}
