<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Validação do Cloudflare Turnstile.
 *
 * O Turnstile é gratuito, não cobra por requisição e não exibe aquelas
 * imagens de semáforo que fazem o usuário desistir.
 *
 * Conta de criação: https://dash.cloudflare.com → Turnstile
 * As chaves de teste abaixo existem para você conseguir rodar o ambiente
 * local sem criar conta nenhuma — NUNCA vão para produção.
 *
 * REGRA IMPORTANTE: este serviço falha fechado. Se a chave secreta não
 * estiver configurada, todo captcha é considerado inválido. É melhor o site
 * parar do que o site ficar sem proteção sem ninguém notar.
 */
class Turnstile
{
    public const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    /**
     * Confere um token vindo do navegador.
     */
    public function verify(?string $token, ?string $ip = null): bool
    {
        if (blank($token)) {
            return false;
        }

        $secret = config('services.turnstile.secret');

        if (blank($secret)) {
            Log::error('turnstile.secret_ausente', [
                'mensagem' => 'TURNSTILE_SECRET nao configurado. Todo captcha sera recusado.',
            ]);

            return false;
        }

        $payload = [
            'secret' => $secret,
            'response' => $token,
        ];

        if (filled($ip)) {
            $payload['remoteip'] = $ip;
        }

        try {
            $response = Http::asForm()
                ->timeout(5)
                ->connectTimeout(3)
                ->post(self::VERIFY_URL, $payload);
        } catch (\Throwable $e) {
            // Se a Cloudflare não respondeu, NÃO é permissão. É falha.
            Log::error('turnstile.indisponivel', [
                'erro' => $e->getMessage(),
            ]);

            return false;
        }

        if (! $response->successful()) {
            Log::warning('turnstile.http_erro', [
                'status' => $response->status(),
                'corpo' => substr((string) $response->body(), 0, 300),
            ]);

            return false;
        }

        $data = $response->json();
        $ok = (bool) ($data['success'] ?? false);

        if (! $ok) {
            Log::info('turnstile.reprovado', [
                'error_codes' => $data['error-codes'] ?? null,
                'ip' => $ip,
            ]);
        }

        return $ok;
    }
}
