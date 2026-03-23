<?php

namespace Database\Seeders;

use App\Models\Team;
use Illuminate\Database\Seeder;

class TeamSeeder extends Seeder
{
    public function run(): void
    {
        $teams = [
            ['name' => 'Mumbai Warriors',  'short_name' => 'MUW', 'color' => '#1e40af'],
            ['name' => 'Delhi Dynamos',    'short_name' => 'DEL', 'color' => '#dc2626'],
            ['name' => 'Chennai Kings',    'short_name' => 'CHK', 'color' => '#d97706'],
            ['name' => 'Kolkata Knights',  'short_name' => 'KOK', 'color' => '#7c3aed'],
        ];

        foreach ($teams as $team) {
            Team::create($team);
        }
    }
}
