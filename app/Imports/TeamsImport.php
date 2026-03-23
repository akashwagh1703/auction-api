<?php

namespace App\Imports;

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class TeamsImport implements ToModel, WithHeadingRow, WithValidation
{
    public function model(array $row): Team
    {
        $team = new Team([
            'name'       => $row['name'],
            'email'      => $row['email'] ?? null,
            'phone'      => $row['phone'] ?? null,
            'short_name' => $row['short_name'] ?? $row['shortname'] ?? strtoupper(substr($row['name'], 0, 3)),
            'color'      => $row['color'] ?? '#3B82F6',
            'logo'       => $row['logo'] ?? null,
        ]);

        $team->save();

        // Auto-create owner login account
        $email = $row['email'] ?? null;
        $phone = $row['phone'] ?? null;
        if ($email && $phone && !User::where('email', $email)->exists()) {
            User::create([
                'name'     => $row['name'],
                'email'    => $email,
                'password' => Hash::make($phone),
                'role'     => 'owner',
                'team_id'  => $team->id,
            ]);
        }

        return $team;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }
}
