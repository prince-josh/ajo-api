<?php

use App\Http\Controllers\API\AjoGroupController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\WalletController;
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\GroupController as AdminGroupController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| AJO SAVINGS API ROUTES
|--------------------------------------------------------------------------
*/

// ── Public routes ────────────────────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login',    [AuthController::class, 'login']);
});

// ── Authenticated routes ─────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me',      [AuthController::class, 'me']);
    });

    // Wallet
    Route::prefix('wallet')->group(function () {
        Route::get('/',                [WalletController::class, 'show']);
        Route::get('transactions',     [WalletController::class, 'transactions']);
        Route::post('fund/initiate',   [WalletController::class, 'initiateFunding']);
        Route::post('fund/verify',     [WalletController::class, 'verifyFunding']);
    });

    // Ajo Groups
    Route::prefix('groups')->group(function () {
        Route::get('/',                        [AjoGroupController::class, 'index']);
        Route::post('/',                       [AjoGroupController::class, 'store']);
        Route::post('join',                    [AjoGroupController::class, 'join']);
        Route::get('{group}',                  [AjoGroupController::class, 'show']);
        Route::post('{group}/start',           [AjoGroupController::class, 'start']);
        Route::get('{group}/board',            [AjoGroupController::class, 'board']);
        Route::post('{group}/contribute',      [AjoGroupController::class, 'contribute']);
        Route::get('{group}/contributions',    [AjoGroupController::class, 'contributions']);
    });

    // ── Admin routes ──────────────────────────────────────────────────────────
    Route::middleware('admin')->prefix('admin')->group(function () {

        // Analytics
        Route::get('analytics', [AnalyticsController::class, 'index']);

        // User management
        Route::prefix('users')->group(function () {
            Route::get('/',                          [AdminUserController::class, 'index']);
            Route::get('defaulters',                 [AdminUserController::class, 'defaulters']);
            Route::get('{user}',                     [AdminUserController::class, 'show']);
            Route::patch('{user}/suspend',           [AdminUserController::class, 'toggleSuspend']);

            // Super admin only
            Route::middleware('admin:super_admin')->group(function () {
                Route::patch('{user}/promote',       [AdminUserController::class, 'promote']);
            });
        });

        // Group management
        Route::prefix('groups')->group(function () {
            Route::get('/',                          [AdminGroupController::class, 'index']);
            Route::get('{group}',                    [AdminGroupController::class, 'show']);
            Route::patch('{group}/suspend',          [AdminGroupController::class, 'toggleSuspend']);
            Route::delete('{group}',                 [AdminGroupController::class, 'destroy']);
        });
    });
});
