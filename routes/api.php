<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AuctionController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\LiveAuctionController;
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

    // Auth — public
    Route::post('/login', [AuthController::class, 'login']);

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

        // Live auction — state + bids: all roles | actions: admin only
        Route::get('/auctions/{auction}/state',  [LiveAuctionController::class, 'state']);
        Route::get('/auctions/{auction}/bids',   [LiveAuctionController::class, 'bids']);
        Route::middleware('role:admin')->group(function () {
            Route::post('/auctions/{auction}/start',       [LiveAuctionController::class, 'start']);
            Route::post('/auctions/{auction}/stop',        [LiveAuctionController::class, 'stop']);
            Route::post('/auctions/{auction}/next-player', [LiveAuctionController::class, 'nextPlayer']);
            Route::post('/auctions/{auction}/sold',        [LiveAuctionController::class, 'soldPlayer']);
            Route::post('/auctions/{auction}/unsold',      [LiveAuctionController::class, 'markUnsold']);
            Route::post('/auctions/{auction}/re-auction',  [LiveAuctionController::class, 'reAuction']);
        });
        // Place bid — owners + admin
        Route::middleware('role:admin,owner')->post('/auctions/{auction}/bid', [LiveAuctionController::class, 'placeBid']);

        // Chat — read + send: all roles
        Route::get('/auctions/{auction}/chat',  [ChatController::class, 'index']);
        Route::post('/auctions/{auction}/chat', [ChatController::class, 'store']);

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
