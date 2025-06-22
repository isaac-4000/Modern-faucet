<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Transaction;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FaucetController extends Controller
{
    /**
     * Claim from faucet.
     */
    public function claim(Request $request)
    {
        $user = $request->user();

        if (!$user->canClaim()) {
            $setting = Setting::where('setting_key', 'faucet_claim_interval_seconds')->first();
            $interval = $setting ? (int)$setting->setting_value : 1800;
            $nextClaimTime = $user->last_claim_at->addSeconds($interval);
            
            return response()->json([
                'message' => 'You must wait before claiming again',
                'next_claim_at' => $nextClaimTime,
            ], 429);
        }

        $claimAmount = Setting::getValue('faucet_claim_amount', '0.00000010');

        DB::transaction(function () use ($user, $claimAmount) {
            // Update user balance and last claim time
            $user->increment('balance', $claimAmount);
            $user->update(['last_claim_at' => now()]);

            // Create transaction record
            Transaction::create([
                'user_id' => $user->id,
                'type' => 'faucet_claim',
                'amount' => $claimAmount,
                'status' => 'completed',
            ]);

            // Handle referral bonus
            if ($user->referred_by) {
                $referrer = User::where('referral_code', $user->referred_by)->first();
                if ($referrer) {
                    $referralPercentage = Setting::getValue('referral_percentage', '0.10');
                    $referralBonus = $claimAmount * $referralPercentage;
                    
                    $referrer->increment('balance', $referralBonus);
                    
                    Transaction::create([
                        'user_id' => $referrer->id,
                        'type' => 'referral_bonus',
                        'amount' => $referralBonus,
                        'status' => 'completed',
                    ]);
                }
            }
        });

        $user->refresh();

        return response()->json([
            'message' => 'Claim successful',
            'amount' => $claimAmount,
            'new_balance' => $user->balance,
        ]);
    }

    /**
     * Get faucet information.
     */
    public function info(Request $request)
    {
        $user = $request->user();
        $claimAmount = Setting::getValue('faucet_claim_amount', '0.00000010');
        $claimInterval = Setting::getValue('faucet_claim_interval_seconds', '1800');
        
        $canClaim = $user->canClaim();
        $nextClaimTime = null;
        
        if (!$canClaim && $user->last_claim_at) {
            $nextClaimTime = $user->last_claim_at->addSeconds((int)$claimInterval);
        }

        return response()->json([
            'claim_amount' => $claimAmount,
            'claim_interval_seconds' => (int)$claimInterval,
            'can_claim' => $canClaim,
            'next_claim_at' => $nextClaimTime,
            'user_balance' => $user->balance,
            'referral_url' => $user->referral_url,
        ]);
    }
}

