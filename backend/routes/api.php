<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FaucetController;
use App\Http\Controllers\Api\DiceController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\WithdrawalController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Regra de segurança deste arquivo:
|
|   auth:sanctum  → "o token é válido?" (não diz válido para QUEM)
|   auth.user     → "o token pertence a um usuário comum?"
|   auth.admin    → "o token pertence a um administrador E tem a habilidade
|                    admin?"
|
| NUNCA use auth:sanctum sozinho numa rota que mexe em saldo, em configuração
| ou em dados de outras pessoas. Foi exatamente esse erro que permitia a
| qualquer usuário logado alterar o próprio saldo.
|
*/

// ---------------------------------------------------------------- rotas públicas

// Estado da torneira (teto diário). É público de propósito: a barra aparece
// na home para quem ainda nem tem conta — é prova de que o dinheiro existe.
Route::get('/faucet/tank', [WithdrawalController::class, 'tank']);

Route::post('/register', [AuthController::class, 'register'])
    ->middleware('throttle:register');

Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:login');

Route::post('/admin/login', [AuthController::class, 'adminLogin'])
    ->middleware('throttle:admin-login');

// ------------------------------------------------- logout (usuário E admin usam)
// Fica fora dos grupos abaixo porque o front-end chama o mesmo endpoint para
// encerrar os dois tipos de sessão.
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
});

// ------------------------------------------------------------ rotas de usuário
Route::middleware(['auth:sanctum', 'auth.user'])->group(function () {
    Route::get('/user', [AuthController::class, 'me']);

    // Faucet routes
    Route::get('/faucet/info', [FaucetController::class, 'info']);

    // throttle:claim é a rede contra script que fica tentando fora do
    // intervalo. Somado ao intervalo de 4 minutos e ao captcha.
    Route::post('/faucet/claim', [FaucetController::class, 'claim'])
        ->middleware('throttle:claim');

    // Saques via FaucetPay
    Route::get('/withdrawals', [WithdrawalController::class, 'index']);
    Route::post('/withdraw', [WithdrawalController::class, 'store'])
        ->middleware('throttle:withdraw');

    // Dice game routes
    Route::post('/dice/play', [DiceController::class, 'play']);
    Route::get('/dice/history', [DiceController::class, 'history']);
    Route::get('/dice/verify/{gameId}', [DiceController::class, 'verify']);
});

// ------------------------------------------------------ rotas de administrador
// auth.admin exige, na ordem: token válido + dono do token ser da tabela
// admins + token carregar a habilidade "admin".
Route::middleware(['auth:sanctum', 'auth.admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard']);
    Route::get('/users', [AdminController::class, 'users']);
    Route::get('/users/{userId}', [AdminController::class, 'userDetails']);
    Route::put('/users/{userId}/balance', [AdminController::class, 'updateUserBalance']);
    Route::get('/transactions', [AdminController::class, 'transactions']);
    Route::get('/dice-games', [AdminController::class, 'diceGames']);
    Route::get('/payouts', [AdminController::class, 'payouts']);
    Route::get('/faucet/status', [AdminController::class, 'faucetStatus']);
    Route::post('/faucet/recalculate-claim', [AdminController::class, 'recalculateClaim']);
    Route::get('/settings', [AdminController::class, 'settings']);
    Route::put('/settings/{settingId}', [AdminController::class, 'updateSetting']);
    Route::post('/admins', [AdminController::class, 'createAdmin']);
    Route::get('/admins', [AdminController::class, 'admins']);
});
