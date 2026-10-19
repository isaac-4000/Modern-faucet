<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payout;
use App\Models\Transaction;
use App\Models\User;
use App\Services\FaucetEconomy;
use App\Services\FaucetPay;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Saque do usuário via FaucetPay.
 *
 * Aqui mora a segurança financeira do faucet, em duas travas:
 *
 *  1. MÍNIMO — ninguém saca antes de juntar 500 pontos (~US$ 0,005).
 *     Motivo prático: a FaucetPay cobra 0,25 DGB por saque. Abaixo disso a
 *     taxa come mais da metade do pagamento.
 *
 *  2. TORNEIRA — existe um teto diário em reais. O que passar dele não sai,
 *     aconteça o que acontecer. É isso que rebaixa um ataque de bots de
 *     "risco existencial" para "risco que eu já contabilizei".
 */
class WithdrawalController extends Controller
{
    protected FaucetEconomy $economy;
    protected FaucetPay $faucetPay;

    public function __construct(FaucetEconomy $economy, FaucetPay $faucetPay)
    {
        $this->economy = $economy;
        $this->faucetPay = $faucetPay;
    }

    /**
     * Estado da torneira. É público de propósito: a barra aparece na home
     * para quem ainda nem tem conta, e ela é a prova de que o dinheiro existe.
     */
    public function tank()
    {
        return response()->json($this->economy->tank());
    }

    /**
     * Histórico de saques do próprio usuário.
     */
    public function index(Request $request)
    {
        $payouts = Payout::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json($payouts);
    }

    /**
     * Pedir um saque.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            // A FaucetPay aceita e-mail, nome de usuário ou endereço da carteira.
            // Por isso não validamos o formato aqui — ela valida.
            'wallet_address' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = $request->user();

        // ------------------------------------------------------ trava 1: torneira
        if ($this->economy->isTankEmpty()) {
            return response()->json([
                'message' => 'A torneira de hoje já secou. Volta amanhã — o tanque enche de novo.',
                'tank' => $this->economy->tank(),
            ], 503);
        }

        $minimumPoints = $this->economy->minimumPayoutPoints();
        $balancePoints = (float) $user->balance;

        // -------------------------------------------------------- trava 2: mínimo
        if ($balancePoints < $minimumPoints) {
            return response()->json([
                'message' => 'Você ainda não chegou no mínimo para sacar.',
                'minimum_points' => $minimumPoints,
                'minimum_crypto' => $this->economy->minimumPayoutCrypto(),
                'your_points' => round($balancePoints, 2),
                'tank' => $this->economy->tank(),
            ], 422);
        }

        // --------------------------------------- quanto dá para pagar hoje
        // Nunca mais do que o saldo, e nunca mais do que sobrou no tanque.
        $remainingBrl = $this->economy->remainingBrl();

        if ($remainingBrl <= 0) {
            return response()->json([
                'message' => 'A torneira de hoje já secou.',
                'tank' => $this->economy->tank(),
            ], 503);
        }

        $price = $this->economy->dgbUsdPrice();

        if ($price <= 0) {
            Log::error('saque.cotacao_invalida', [
                'dgb_usd_price' => $price,
                'usd_brl_rate' => $this->economy->usdBrlRate(),
            ]);

            return response()->json([
                'message' => 'Cotação não configurada. Avisamos a administração.',
            ], 500);
        }

        // Arredonda para baixo: é melhor sobrar centavo no tanque do que
        // estourar o teto por causa do arredondamento.
        $affordablePoints = floor($this->economy->pointsFromBrl($remainingBrl));
        $points = (float) min($balancePoints, $affordablePoints);

        if ($points < $minimumPoints) {
            return response()->json([
                'message' => 'O que sobrou na torneira hoje não cobre o mínimo de saque. Volta amanhã.',
                'tank' => $this->economy->tank(),
            ], 503);
        }

        $idempotencyKey = (string) Str::uuid();

        // -------------------------------------------------- debita e registra
        $payout = null;

        DB::transaction(function () use ($user, $request, $points, $idempotencyKey, &$payout) {
            // lockForUpdate impede que dois pedidos simultâneos gastem o mesmo
            // saldo duas vezes.
            $locked = User::where('id', $user->id)->lockForUpdate()->first();

            if ((float) $locked->balance < $points) {
                abort(422, 'Saldo insuficiente.');
            }

            // Confere o tanque DE NOVO, agora dentro da transação. Entre a
            // checagem lá em cima e esta linha, outro saque pode ter passado
            // e levado a última gota.
            if ($this->economy->isTankEmpty()) {
                abort(503, 'A torneira de hoje já secou. Volta amanhã.');
            }

            $locked->decrement('balance', $points);

            Transaction::create([
                'user_id' => $locked->id,
                'type' => 'withdrawal',
                'amount' => -1 * $points,
                'currency' => 'points',
                'status' => 'pending',
            ]);

            $payout = Payout::create([
                'user_id' => $locked->id,
                'wallet_address' => $request->wallet_address,
                'amount' => $this->economy->cryptoFromPoints($points),
                'currency' => $this->economy->payoutCurrency(),
                'idempotency_key' => $idempotencyKey,
                'amount_brl' => number_format($this->economy->brlFromPoints($points), 8, '.', ''),
                'points_amount' => number_format($points, 8, '.', ''),
                'status' => 'pending',
                'ip_address' => $request->ip(),
            ]);
        });

        $amountCrypto = (string) $payout->amount;
        $amountBrl = (float) $payout->amount_brl;

        // ------------------------------------------------------ chama a API
        $result = $this->faucetPay->send(
            $this->economy->payoutCurrency(),
            $amountCrypto,
            $request->wallet_address,
            $request->ip(),
            $idempotencyKey
        );

        if ($result['ok']) {
            $payout->update([
                'status' => 'completed',
                'transaction_id' => $result['payout_id'],
                'processed_at' => now(),
            ]);

            Transaction::where('user_id', $user->id)
                ->where('type', 'withdrawal')
                ->where('status', 'pending')
                ->update(['status' => 'completed']);

            Log::info('saque.concluido', [
                'user_id' => $user->id,
                'payout_id' => $payout->id,
                'points' => $points,
                'amount' => $amountCrypto,
                'currency' => $this->economy->payoutCurrency(),
                'amount_brl' => round($amountBrl, 2),
                'idempotency_key' => $idempotencyKey,
            ]);

            return response()->json([
                'message' => 'Saque enviado.',
                'points' => $points,
                'amount' => $amountCrypto,
                'currency' => $this->economy->payoutCurrency(),
                'payout' => $payout->fresh(),
                'tank' => $this->economy->tank(),
            ]);
        }

        // Falhou: devolve o saldo. O dinheiro do usuário nunca desaparece.
        DB::transaction(function () use ($user, $points, $payout, $result) {
            User::where('id', $user->id)->lockForUpdate()->first()
                ?->increment('balance', $points);

            $payout->update([
                'status' => 'failed',
                'error_message' => $result['error'],
            ]);

            Transaction::where('user_id', $user->id)
                ->where('type', 'withdrawal')
                ->where('status', 'pending')
                ->update(['status' => 'failed']);
        });

        Log::warning('saque.falhou', [
            'user_id' => $user->id,
            'payout_id' => $payout->id,
            'points' => $points,
            'erro' => $result['error'],
        ]);

        return response()->json([
            'message' => 'Não foi possível enviar o saque agora. Seu saldo foi devolvido.',
            'error' => $result['error'],
            'tank' => $this->economy->tank(),
        ], 502);
    }
}
