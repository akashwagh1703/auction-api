<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AuctionController;
use App\Http\Controllers\Api\BiddingController;
use App\Http\Controllers\Api\PlayerController;
use App\Http\Controllers\Api\TeamController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ResetController;
use App\Http\Controllers\Api\SettingsController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    Route::get('/ping', fn() => response()->json(['message' => 'Auction API v1 is running', 'status' => 'ok']));

    // Settings — GET is public (login page needs branding)
    Route::get('/settings', [SettingsController::class, 'index']);

    // Auth — public (rate limited: 5 requests per minute)
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

    // All authenticated routes
    Route::middleware('auth:sanctum')->group(function () {

        // Auth
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me',      [AuthController::class, 'me']);

        // Teams — read: all roles | write: admin only
        Route::get('/teams',          [TeamController::class, 'index']);
        Route::get('/teams/{team}',   [TeamController::class, 'show']);
        Route::middleware('role:admin')->group(function () {
            Route::post('/teams',             [TeamController::class, 'store']);
            Route::put('/teams/{team}',       [TeamController::class, 'update']);
            Route::delete('/teams/{team}',    [TeamController::class, 'destroy']);
            Route::post('/teams/import',      [TeamController::class, 'import']);
        });

        // Players — read: all roles | write: admin only
        Route::get('/players',          [PlayerController::class, 'index']);
        Route::get('/players/{player}', [PlayerController::class, 'show']);
        Route::middleware('role:admin')->group(function () {
            Route::post('/players',              [PlayerController::class, 'store']);
            Route::put('/players/{player}',      [PlayerController::class, 'update']);
            Route::delete('/players/{player}',   [PlayerController::class, 'destroy']);
            Route::post('/players/import',       [PlayerController::class, 'import']);
        });

        // Auctions — read: all roles | write: admin only
        Route::get('/auctions',             [AuctionController::class, 'index']);
        Route::get('/auctions/{auction}',   [AuctionController::class, 'show']);
        Route::middleware('role:admin')->group(function () {
            Route::post('/auctions',                          [AuctionController::class, 'store']);
            Route::put('/auctions/{auction}',                 [AuctionController::class, 'update']);
            Route::delete('/auctions/{auction}',              [AuctionController::class, 'destroy']);
            Route::put('/auctions/{auction}/teams',           [AuctionController::class, 'attachTeams']);
            Route::put('/auctions/{auction}/players',         [AuctionController::class, 'attachPlayers']);
        });

        // Bidding — HTTP-based polling (replaces WebSocket)
        Route::get('/auctions/{auction}/state',     [BiddingController::class, 'getAuctionState']);
        Route::middleware('role:admin,owner')->group(function () {
            Route::post('/auctions/{auction}/players/{player}/bid', [BiddingController::class, 'placeBid']);
        });
        Route::middleware('role:admin')->group(function () {
            Route::post('/auctions/{auction}/players/{player}/sold',   [BiddingController::class, 'markSold']);
            Route::post('/auctions/{auction}/players/{player}/unsold', [BiddingController::class, 'markUnsold']);
            Route::post('/auctions/{auction}/next-player',           [BiddingController::class, 'nextPlayer']);
        });

        // Users — admin only
        Route::middleware('role:admin')->group(function () {
            Route::get('/users',          [UserController::class, 'index']);
            Route::post('/users',         [UserController::class, 'store']);
            Route::get('/users/{user}',   [UserController::class, 'show']);
            Route::put('/users/{user}',   [UserController::class, 'update']);
            Route::delete('/users/{user}',[UserController::class, 'destroy']);
            // Reset all data
            Route::post('/reset',         [ResetController::class, 'reset']);
            // Settings write — admin only
            Route::put('/settings',                [SettingsController::class, 'update']);
            Route::post('/settings/logo',          [SettingsController::class, 'uploadLogo']);
            Route::put('/settings/password',       [SettingsController::class, 'changePassword']);
            Route::put('/settings/profile',        [SettingsController::class, 'updateProfile']);
        });

    });

});
