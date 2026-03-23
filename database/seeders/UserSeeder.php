<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin
        User::create([
            'name'     => 'Admin User',
            'email'    => 'admin@auction.com',
            'password' => Hash::make('admin123'),
            'role'     => 'admin',
        ]);

        // Owners — team_id matches seeded teams (1–4)
        User::create([
            'name'     => 'Team Mumbai',
            'email'    => 'mumbai@auction.com',
            'password' => Hash::make('owner123'),
            'role'     => 'owner',
            'team_id'  => 1,
        ]);

        User::create([
            'name'     => 'Team Delhi',
            'email'    => 'delhi@auction.com',
            'password' => Hash::make('owner123'),
            'role'     => 'owner',
            'team_id'  => 2,
        ]);

        User::create([
            'name'     => 'Team Chennai',
            'email'    => 'chennai@auction.com',
            'password' => Hash::make('owner123'),
            'role'     => 'owner',
            'team_id'  => 3,
        ]);

        User::create([
            'name'     => 'Team Kolkata',
            'email'    => 'kolkata@auction.com',
            'password' => Hash::make('owner123'),
            'role'     => 'owner',
            'team_id'  => 4,
        ]);

        // Player — player_id matches seeded player (Virat Kohli = 1)
        User::create([
            'name'      => 'Virat Kohli',
            'email'     => 'virat@auction.com',
            'password'  => Hash::make('player123'),
            'role'      => 'player',
            'player_id' => 1,
        ]);
    }
}
