<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            // Branding
            ['key' => 'app_name',              'value' => 'AuctionPro',               'type' => 'string'],
            ['key' => 'app_tagline',           'value' => 'Player Auction Platform',  'type' => 'string'],
            ['key' => 'app_logo',              'value' => '🏏',                       'type' => 'string'],
            ['key' => 'app_primary_color',     'value' => '#2563eb',                  'type' => 'string'],

            // Login page
            ['key' => 'show_demo_login',       'value' => 'true',                     'type' => 'boolean'],
            ['key' => 'login_welcome_message', 'value' => 'Welcome back! Please sign in to continue.', 'type' => 'string'],

            // Auction defaults
            ['key' => 'default_bid_timer',          'value' => '30',                       'type' => 'number'],
            ['key' => 'default_budget_per_team',     'value' => '1000000',                  'type' => 'number'],
            ['key' => 'default_max_players',         'value' => '15',                       'type' => 'number'],
            ['key' => 'default_bid_increments',      'value' => '10000,25000,50000,100000', 'type' => 'string'],
        ];

        foreach ($defaults as $setting) {
            DB::table('settings')->updateOrInsert(
                ['key' => $setting['key']],
                ['value' => $setting['value'], 'type' => $setting['type'], 'updated_at' => now(), 'created_at' => now()]
            );
        }
    }
}
