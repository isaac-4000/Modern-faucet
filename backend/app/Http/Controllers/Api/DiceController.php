<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\DiceGame;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class DiceController extends Controller
{
    /**
     * Play dice game.
     */
    public function play(Request $request)
    {
        $validator = Validator::make($request->all(), [
            // Apostas em PONTOS (1 ponto = US$ 0,00001).
            // Teto de 50 pontos = US$ 0,0005 por jogada: protege o caixa sem
            // impedir quem quer apostar de verdade.
            'bet_amount' => 'required|numeric|min:1|max:50',
            'target_number' => 'required|numeric|min:2|max:97',
            'client_seed' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = $request->user();
        $betAmount = $request->bet_amount;
        $targetNumber = $request->target_number;
        $clientSeed = $request->client_seed;

        // Check if user has sufficient balance
        if ($user->balance < $betAmount) {
            return response()->json(['message' => 'Insufficient balance'], 400);
        }

        // Generate server seed and nonce
        $serverSeed = Str::random(64);
        $hashedServerSeed = hash('sha256', $serverSeed);
        $nonce = $user->diceGames()->count() + 1;

        // Generate roll result using provably fair algorithm
        $rollResult = DiceGame::generateRoll($serverSeed, $clientSeed, $nonce);

        // Determine if user wins
        $isWin = $rollResult < $targetNumber;

        // Calculate payout
        $multiplier = $isWin ? (99 / $targetNumber) * 0.98 : 0; // 2% house edge
        $payoutAmount = $isWin ? $betAmount * $multiplier : 0;
        $netProfit = $payoutAmount - $betAmount;

        DB::transaction(function () use ($user, $betAmount, $rollResult, $targetNumber, $isWin, $payoutAmount, $netProfit, $serverSeed, $clientSeed, $nonce, $hashedServerSeed) {
            // Deduct bet amount from user balance
            $user->decrement('balance', $betAmount);

            // Add payout to user balance if win
            if ($isWin && $payoutAmount > 0) {
                $user->increment('balance', $payoutAmount);
            }

            // Create dice game record
            DiceGame::create([
                'user_id' => $user->id,
                'bet_amount' => $betAmount,
                'roll_result' => $rollResult,
                'target_number' => $targetNumber,
                'is_win' => $isWin,
                'payout_amount' => $payoutAmount,
                'server_seed' => $serverSeed,
                'client_seed' => $clientSeed,
                'nonce' => $nonce,
                'hashed_server_seed' => $hashedServerSeed,
            ]);

            // Create transaction records
            Transaction::create([
                'user_id' => $user->id,
                'type' => $isWin ? 'dice_win' : 'dice_loss',
                'amount' => $netProfit,
                'currency' => 'points',
                'status' => 'completed',
            ]);
        });

        $user->refresh();

        return response()->json([
            'roll_result' => $rollResult,
            'target_number' => $targetNumber,
            'is_win' => $isWin,
            'bet_amount' => $betAmount,
            'payout_amount' => $payoutAmount,
            'net_profit' => $netProfit,
            'new_balance' => $user->balance,
            'server_seed' => $serverSeed,
            'client_seed' => $clientSeed,
            'nonce' => $nonce,
            'hashed_server_seed' => $hashedServerSeed,
        ]);
    }

    /**
     * Get game history.
     */
    public function history(Request $request)
    {
        $user = $request->user();
        $games = $user->diceGames()
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($games);
    }

    /**
     * Verify a game result.
     */
    public function verify(Request $request, $gameId)
    {
        $user = $request->user();
        $game = $user->diceGames()->findOrFail($gameId);

        $isValid = $game->verifyRoll();

        return response()->json([
            'game_id' => $game->id,
            'is_valid' => $isValid,
            'server_seed' => $game->server_seed,
            'client_seed' => $game->client_seed,
            'nonce' => $game->nonce,
            'roll_result' => $game->roll_result,
            'expected_roll' => DiceGame::generateRoll($game->server_seed, $game->client_seed, $game->nonce),
        ]);
    }
}

