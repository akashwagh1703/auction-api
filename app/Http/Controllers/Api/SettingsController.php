<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    // Public — used by frontend on boot (login page needs branding)
    public function index()
    {
        return response()->json(Setting::allAsObject());
    }

    // Admin only — update multiple settings at once
    public function update(Request $request)
    {
        $data = $request->validate([
            'app_name'                   => 'sometimes|string|max:100',
            'app_tagline'                => 'sometimes|string|max:200',
            'app_logo'                   => 'sometimes|nullable|string|max:500',
            'app_primary_color'          => 'sometimes|string|max:7',
            'show_demo_login'            => 'sometimes|boolean',
            'login_welcome_message'      => 'sometimes|string|max:300',
            'default_bid_timer'          => 'sometimes|integer|min:10|max:120',
            'default_budget_per_team'    => 'sometimes|integer|min:1',
            'default_max_players'        => 'sometimes|integer|min:1',
            'default_bid_increments'     => 'sometimes|string|max:200',
        ]);

        foreach ($data as $key => $value) {
            Setting::set($key, $value);
        }

        return response()->json(Setting::allAsObject());
    }

    // Admin only — upload logo image file
    public function uploadLogo(Request $request)
    {
        $request->validate([
            'logo' => 'required|file|image|mimes:png,jpg,jpeg,svg,webp|max:2048',
        ]);

        // Delete old uploaded logo if it exists
        $existing = Setting::get('app_logo', '');
        if ($existing && str_starts_with($existing, '/storage/')) {
            Storage::disk('public')->delete(str_replace('/storage/', '', $existing));
        }

        $path = $request->file('logo')->store('logos', 'public');
        $url  = '/storage/' . $path;

        Setting::set('app_logo', $url);

        return response()->json(['url' => $url]);
    }

    // Admin only — change own password
    public function changePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => 'required|string',
            'new_password'     => 'required|string|min:6|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($data['current_password'], $user->password)) {
            return response()->json(['message' => 'Current password is incorrect'], 422);
        }

        $user->update(['password' => Hash::make($data['new_password'])]);

        return response()->json(['message' => 'Password updated successfully']);
    }

    // Admin only — update admin profile (name + email)
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name'  => 'required|string|max:100',
            'email' => 'required|email|unique:users,email,' . $user->id,
        ]);

        $user->update($data);

        return response()->json([
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
            'role'  => $user->role,
        ]);
    }
}
