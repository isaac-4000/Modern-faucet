<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use App\Rules\TurnstileToken;
use App\Services\FaucetEconomy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class FaucetController extends Controller
{
    /**
     * Claim do faucet.
     */
    public function claim(Request $request)
    {
        // garante que as configurações de economia existem no banco
        Setting::firstOrCreate(
            ['setting_key' => 'faucet_claim_interval_seconds'],
            ['setting_value' => '240', 'description' => 'Tempo em segundos entre claims do faucet.']
        );

        Setting::firstOrCreate(
            ['setting_key' => 'faucet_claim_amount'],
            ['setting_value' => '11', 'description' => 'Pontos ganhos por claim do faucet.']
        );

        $user = $request->user();
        $ip = $request->ip();

        if (! $user->canClaim()) {
            $interval = (int) Setting::getValue('faucet_claim_interval_seconds', '240');
            $nextClaimTime = $user->last_claim_at->addSeconds($interval);

            return response()->json([
                'message' => 'Você precisa esperar para fazer um novo claim.',
                'next_claim_at' => $nextClaimTime,
            ], 429);
        }

        // ------------------------------------------------------------- captcha
        // É o que separa um humano de um script. Sem isso o intervalo de
        // 4 minutos não vale nada: um bot só abriria 15 abas.
        $validator = Validator::make($request->all(), [
            'cf-turnstile-response' => ['required', new TurnstileToken($ip)],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $claimAmount = app(FaucetEconomy::class)->claimAmountPoints();

        DB::transaction(function () use ($user, $claimAmount, $ip) {
            $user->increment('balance', $claimAmount);
            $user->update([
                'last_claim_at' => now(),
                'last_claim_ip' => $ip,
            ]);

            Transaction::create([
                'user_id' => $user->id,
                'type' => 'faucet_claim',
                'amount' => $claimAmount,
                'currency' => 'points',
                'status' => 'completed',
            ]);

            // bônus de indicação
            if ($user->referred_by) {
                $referrer = User::where('referral_code', $user->referred_by)->first();
                if ($referrer) {
                    $referralPercentage = (float) Setting::getValue('referral_percentage', '0.10');
                    $referralBonus = $claimAmount * $referralPercentage;

                    $referrer->increment('balance', $referralBonus);

                    Transaction::create([
                        'user_id' => $referrer->id,
                        'type' => 'referral_bonus',
                        'amount' => $referralBonus,
                        'currency' => 'points',
                        'status' => 'completed',
                    ]);
                }
            }
        });

        $user->refresh();

        return response()->json([
            'message' => 'Claim feito. '.$claimAmount.' pontos foram para o seu saldo.',
            'amount' => $claimAmount,
            'new_balance' => $user->balance,
            'next_claim_in' => (int) Setting::getValue('faucet_claim_interval_seconds', '240'),
        ]);
    }

    /**
     * Informações do faucet para montar a tela.
     */
    public function info(Request $request)
    {
        $economy = app(FaucetEconomy::class);

        $user = $request->user();
        $claimAmount = $economy->claimAmountPoints();
        $claimInterval = $economy->claimIntervalSeconds();

        $canClaim = $user->canClaim();
        $nextClaimTime = null;

        if (! $canClaim && $user->last_claim_at) {
            $nextClaimTime = $user->last_claim_at->addSeconds($claimInterval);
        }

        return response()->json([
            'claim_amount' => $claimAmount,
            'claim_interval_seconds' => $claimInterval,
            'can_claim' => $canClaim,
            'next_claim_at' => $nextClaimTime,
            'user_balance' => $user->balance,
            'referral_url' => $user->referral_url,

            // o que a tela de saque precisa saber
            'minimum_payout_points' => $economy->minimumPayoutPoints(),
            'minimum_payout_crypto' => $economy->minimumPayoutCrypto(),
            'payout_currency' => $economy->payoutCurrency(),
            'tank' => $economy->tank(),
        ]);
    }
}
