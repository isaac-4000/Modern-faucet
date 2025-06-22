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
        $setting->update([
            'setting_value' => $request->setting_value,
            'description' => $request->description ?? $setting->description,
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
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $admin = Admin::create([
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
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

