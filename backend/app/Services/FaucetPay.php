<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Cliente da API da FaucetPay (microcarteira).
 *
 * É o que substitui a integração com blockchain, que seria inviável: mandar
 * 0,03 DGB direto para a carteira de cada usuário custaria mais em taxa do
 * que o próprio pagamento.
 *
 * Documentação: https://faucetpay.io/api-docs
 *
 * SOBRE O TETO DIÁRIO
 * A FaucetPay também permite travar um teto diário em dólar na própria chave
 * de API (v2). Configure isso no painel deles como SEGUNDA rede de segurança.
 * A primeira é o teto calculado no nosso banco, que é o que aparece na barra
 * da torneira para o usuário.
 */
class FaucetPay
{
    public const SEND_URL = 'https://faucetpay.io/api/v1/send';

    /**
     * Casas decimais da menor unidade da moeda.
     * DGB, BTC e LTC usam 8. DOGE também.
     */
    public const DECIMALS = 8;

    /**
     * Envia um pagamento para um usuário da FaucetPay.
     *
     * @return array{ok: bool, payout_id: string|null, error: string|null}
     */
    public function send(
        string $currency,
        string $amount,
        string $to,
        ?string $ip = null,
        ?string $idempotencyKey = null
    ): array {
        $apiKey = config('services.faucetpay.api_key');

        if (blank($apiKey)) {
            Log::error('faucetpay.sem_chave', [
                'mensagem' => 'FAUCETPAY_API_KEY nao configurado. Nenhum saque pode ser feito.',
            ]);

            return ['ok' => false, 'payout_id' => null, 'error' => 'Chave da FaucetPay não configurada.'];
        }

        $payload = [
            'api_key' => $apiKey,
            'amount' => $this->toSmallestUnit($amount),
            'to' => $to,
            'currency' => strtoupper($currency),
            'referral' => 'false',
        ];

        if (filled($ip)) {
            $payload['ip_address'] = $ip;
        }

        // Se a nossa conexão cair no meio do caminho e o servidor repetir a
        // chamada, a FaucetPay reconhece a chave e não paga duas vezes.
        if (filled($idempotencyKey)) {
            $payload['idempotency_key'] = $idempotencyKey;
        }

        try {
            $response = Http::asForm()
                ->timeout(15)
                ->connectTimeout(5)
                ->post(self::SEND_URL, $payload);
        } catch (\Throwable $e) {
            Log::error('faucetpay.indisponivel', [
                'erro' => $e->getMessage(),
                'destino' => $to,
                'moeda' => $currency,
            ]);

            return ['ok' => false, 'payout_id' => null, 'error' => 'FaucetPay não respondeu. Tente de novo em alguns minutos.'];
        }

        $data = $response->json();
        $status = $data['status'] ?? null;

        // A API da FaucetPay responde 200 com status 200/4xx dentro do JSON.
        if ($response->successful() && (int) $status === 200) {
            return [
                'ok' => true,
                'payout_id' => isset($data['payout_id']) ? (string) $data['payout_id'] : null,
                'error' => null,
            ];
        }

        $message = $data['message'] ?? 'Resposta inesperada da FaucetPay.';

        Log::warning('faucetpay.recusado', [
            'status_http' => $response->status(),
            'status_api' => $status,
            'mensagem' => $message,
            'destino' => $to,
            'moeda' => $currency,
            'valor' => $amount,
        ]);

        return [
            'ok' => false,
            'payout_id' => null,
            'error' => is_string($message) ? $message : 'Resposta inesperada da FaucetPay.',
        ];
    }

    /**
     * Converte um valor em moeda para a menor unidade inteira.
     *
     * A API exige inteiro: 0,03113 DGB vira 3113000.
     * Passamos por string porque float erraria casas decimais em valor
     * pequeno — e erro de centavo aqui é dinheiro duplicado ou perdido.
     */
    public function toSmallestUnit(string $amount, int $decimals = self::DECIMALS): int
    {
        $normalized = number_format((float) $amount, $decimals, '.', '');

        return (int) str_replace('.', '', $normalized);
    }
}
