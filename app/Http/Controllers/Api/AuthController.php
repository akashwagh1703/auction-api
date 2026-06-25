<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $email = $request->email;
        $lockoutKey = "login_attempt:{$email}";
        $attempts = Cache::get($lockoutKey, 0);

        // Check if account is locked (5 failed attempts = 15 minute lockout)
        if ($attempts >= 5) {
            $remainingTime = Cache::get("login_lockout:{$email}", 0) - time();
            return response()->json([
                'message' => 'Account temporarily locked due to too many failed attempts',
                'retry_after' => $remainingTime > 0 ? $remainingTime : 900
            ], 429);
        }

        $user = User::with(['team', 'player'])
            ->where('email', $email)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            // Increment failed attempts
            $newAttempts = $attempts + 1;
            Cache::put($lockoutKey, $newAttempts, now()->addMinutes(15));

            // Lock account after 5 failed attempts
            if ($newAttempts >= 5) {
                Cache::put("login_lockout:{$email}", time() + 900, now()->addMinutes(15));
                return response()->json([
                    'message' => 'Account locked due to too many failed attempts. Try again in 15 minutes.',
                    'attempts_remaining' => 0
                ], 429);
            }

            return response()->json([
                'message' => 'Invalid credentials',
                'attempts_remaining' => 5 - $newAttempts
            ], 401);
        }

        // Clear failed attempts on successful login
        Cache::forget($lockoutKey);
        Cache::forget("login_lockout:{$email}");

        // Create session-based token for SPA with cookies
        $request->session()->regenerate();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user'  => new UserResource($user),
        ])->withCookie(cookie('auth_token', $token, 60 * 24 * 7, '/', null, true, true, false, 'Lax'));
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out'])
            ->withCookie(cookie('auth_token', '', -1, '/', null, true, true, false, 'Lax'));
    }

    public function me(Request $request)
    {
        return new UserResource($request->user()->load(['team', 'player']));
    }
}
