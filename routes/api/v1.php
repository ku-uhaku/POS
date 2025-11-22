<?php

use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\ProfileController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\RoleController;
use App\Http\Controllers\Api\V1\UserController;
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
    // User management routes
    Route::apiResource('users', UserController::class)->only(['index', 'show', 'destroy']);
    
    // Role management routes
    Route::apiResource('roles', RoleController::class);
});

