<?php

namespace Database\Seeders;

use App\Models\Player;
use Illuminate\Database\Seeder;

class PlayerSeeder extends Seeder
{
    public function run(): void
    {
        $players = [
            ['name' => 'Virat Kohli',     'role' => 'Batsman',        'nationality' => 'India', 'age' => 35, 'base_price' => 200000, 'stats' => ['rating' => 95]],
            ['name' => 'Rohit Sharma',    'role' => 'Batsman',        'nationality' => 'India', 'age' => 36, 'base_price' => 180000, 'stats' => ['rating' => 92]],
            ['name' => 'Jasprit Bumrah',  'role' => 'Bowler',         'nationality' => 'India', 'age' => 30, 'base_price' => 150000, 'stats' => ['rating' => 94]],
            ['name' => 'MS Dhoni',        'role' => 'Wicket-Keeper',  'nationality' => 'India', 'age' => 42, 'base_price' => 200000, 'stats' => ['rating' => 90]],
            ['name' => 'Hardik Pandya',   'role' => 'All-Rounder',    'nationality' => 'India', 'age' => 30, 'base_price' => 160000, 'stats' => ['rating' => 88]],
            ['name' => 'KL Rahul',        'role' => 'Batsman',        'nationality' => 'India', 'age' => 32, 'base_price' => 140000, 'stats' => ['rating' => 87]],
            ['name' => 'Ravindra Jadeja', 'role' => 'All-Rounder',    'nationality' => 'India', 'age' => 35, 'base_price' => 130000, 'stats' => ['rating' => 89]],
            ['name' => 'Shubman Gill',    'role' => 'Batsman',        'nationality' => 'India', 'age' => 24, 'base_price' => 120000, 'stats' => ['rating' => 85]],
        ];

        foreach ($players as $player) {
            Player::create($player);
        }
    }
}
