<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FaucetController;
use App\Http\Controllers\Api\DiceController;
use App\Http\Controllers\Api\AdminController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/admin/login', [AuthController::class, 'adminLogin']);

// Protected user routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // Faucet routes
    Route::get('/faucet/info', [FaucetController::class, 'info']);
    Route::post('/faucet/claim', [FaucetController::class, 'claim']);
    
    // Dice game routes
    Route::post('/dice/play', [DiceController::class, 'play']);
    Route::get('/dice/history', [DiceController::class, 'history']);
    Route::get('/dice/verify/{gameId}', [DiceController::class, 'verify']);
});

// Protected admin routes
Route::middleware(['auth:sanctum'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard']);
    Route::get('/users', [AdminController::class, 'users']);
    Route::get('/users/{userId}', [AdminController::class, 'userDetails']);
    Route::put('/users/{userId}/balance', [AdminController::class, 'updateUserBalance']);
    Route::get('/transactions', [AdminController::class, 'transactions']);
    Route::get('/dice-games', [AdminController::class, 'diceGames']);
    Route::get('/payouts', [AdminController::class, 'payouts']);
    Route::get('/settings', [AdminController::class, 'settings']);
    Route::put('/settings/{settingId}', [AdminController::class, 'updateSetting']);
    Route::post('/admins', [AdminController::class, 'createAdmin']);
    Route::get('/admins', [AdminController::class, 'admins']);
});

