<?php

use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\ProfileController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\RoleController;
use App\Http\Controllers\Api\V1\StoreController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\UserStoreController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/register', RegisterController::class);
    Route::post('/login', LoginController::class);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', LogoutController::class);
        Route::get('/profile', [ProfileController::class, 'show']);
        Route::put('/profile', [ProfileController::class, 'update']);
        Route::patch('/profile', [ProfileController::class, 'update']);
    });
});

// Protected routes with permission checks
Route::middleware('auth:sanctum')->group(function () {
    Route::get('test', function () {
        return response()->json([
            'message' => 'Hello World',
        ]);
    });
    // User management routes
    Route::apiResource('users', UserController::class)->only(['index', 'show', 'destroy']);

    // User store management routes
    Route::get('users/{user}/stores', [UserStoreController::class, 'index']);
    Route::post('users/{user}/stores', [UserStoreController::class, 'store']);
    Route::delete('users/{user}/stores/{store}', [UserStoreController::class, 'destroy']);
    Route::put('users/{user}/default-store', [UserStoreController::class, 'setDefaultStore']);

    // Role management routes
    Route::apiResource('roles', RoleController::class);

    // Store management routes
    Route::apiResource('stores', StoreController::class);
    Route::post('stores/switch', [StoreController::class, 'switchStore']);
});
