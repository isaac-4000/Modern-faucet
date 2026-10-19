<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Admin;
use App\Models\Transaction;
use App\Models\DiceGame;
use App\Models\Payout;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminController extends Controller
{
    /**
     * Get dashboard statistics.
     */
    public function dashboard()
    {
        $stats = [
            'total_users' => User::count(),
            'total_transactions' => Transaction::count(),
            'total_dice_games' => DiceGame::count(),
            'total_payouts' => Payout::count(),
            'total_balance' => User::sum('balance'),
            'total_faucet_claims' => Transaction::where('type', 'faucet_claim')->sum('amount'),
            'total_dice_wins' => Transaction::where('type', 'dice_win')->sum('amount'),
            'total_dice_losses' => abs(Transaction::where('type', 'dice_loss')->sum('amount')),
            'recent_users' => User::orderBy('created_at', 'desc')->take(5)->get(),
            'recent_transactions' => Transaction::with('user')->orderBy('created_at', 'desc')->take(10)->get(),
        ];

        return response()->json($stats);
    }

    /**
     * Get all users with pagination.
     */
    public function users(Request $request)
    {
        $users = User::with(['transactions', 'diceGames', 'payouts'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($users);
    }

    /**
     * Get user details.
     */
    public function userDetails($userId)
    {
        $user = User::with(['transactions', 'diceGames', 'payouts'])->findOrFail($userId);

        return response()->json($user);
    }

    /**
     * Update user balance.
     */
    public function updateUserBalance(Request $request, $userId)
    {
        $validator = Validator::make($request->all(), [
            'balance' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::findOrFail($userId);
        $oldBalance = $user->balance;
        $newBalance = $request->balance;
        $difference = $newBalance - $oldBalance;

        // Auditoria: quem mudou o saldo de quem, de quanto para quanto, e de
        // onde. Sem isso, um ajuste indevido é impossível de rastrear depois.
        // TODO: quando a unidade mudar de BTC para pontos (ver plano de
        // negócio, seção 3.4), revisar a validação de 'balance' abaixo.
        Log::info('admin.balance_updated', [
            'admin_id' => $request->user()?->id,
            'admin_username' => $request->user()?->username,
            'user_id' => $user->id,
            'old_balance' => $oldBalance,
            'new_balance' => $newBalance,
            'difference' => $difference,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        DB::transaction(function () use ($user, $newBalance, $difference) {
            $user->update(['balance' => $newBalance]);

            // Create transaction record for balance adjustment
            if ($difference != 0) {
                Transaction::create([
                    'user_id' => $user->id,
                    'type' => $difference > 0 ? 'faucet_claim' : 'withdrawal',
                    'amount' => $difference,
                    'status' => 'completed',
                ]);
            }
        });

        return response()->json([
            'message' => 'User balance updated successfully',
            'old_balance' => $oldBalance,
            'new_balance' => $newBalance,
        ]);
    }

    /**
     * Get all transactions with pagination.
     */
    public function transactions(Request $request)
    {
        $transactions = Transaction::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return response()->json($transactions);
    }

    /**
     * Get all dice games with pagination.
     */
    public function diceGames(Request $request)
    {
        $games = DiceGame::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return response()->json($games);
    }

    /**
     * Get all payouts with pagination.
     */
    public function payouts(Request $request)
    {
        $payouts = Payout::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return response()->json($payouts);
    }

    /**
     * Get all settings.
     */
    public function settings()
    {
        $settings = Setting::all();

        return response()->json($settings);
    }

    /**
     * Update a setting.
     */
    public function updateSetting(Request $request, $settingId)
    {
        $validator = Validator::make($request->all(), [
            'setting_value' => 'required|string',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $setting = Setting::findOrFail($settingId);
        $oldValue = $setting->setting_value;

        $setting->update([
            'setting_value' => $request->setting_value,
            'description' => $request->description ?? $setting->description,
        ]);

        Log::info('admin.setting_updated', [
            'admin_id' => $request->user()?->id,
            'admin_username' => $request->user()?->username,
            'setting_key' => $setting->setting_key,
            'old_value' => $oldValue,
            'new_value' => $setting->setting_value,
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Setting updated successfully',
            'setting' => $setting,
        ]);
    }

    /**
     * Create a new admin.
     */
    public function createAdmin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|unique:admins',
            'email' => 'nullable|email|unique:admins',
            // Conta de administrador mexe no saldo de todo mundo: a senha
            // precisa ser forte de verdade, não "123456".
            'password' => 'required|string|min:12',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $admin = Admin::create([
            'username' => $request->username,
            'email' => $request->email,
            // O model Admin tem o cast 'password' => 'hashed'. Passamos a
            // senha em texto puro e deixamos o cast fazer o hash.
            'password' => $request->password,
        ]);

        Log::info('admin.created', [
            'created_by_admin_id' => $request->user()?->id,
            'created_by_admin_username' => $request->user()?->username,
            'new_admin_id' => $admin->id,
            'new_admin_username' => $admin->username,
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Admin created successfully',
            'admin' => $admin,
        ], 201);
    }

    /**
     * Get all admins.
     */
    public function admins()
    {
        $admins = Admin::all();

        return response()->json($admins);
    }
}

