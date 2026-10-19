<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Garante que o token Sanctum usado na requisicao pertence a um USUARIO comum.
 *
 * E a contrapartida do EnsureAuthenticatedAsAdmin. Sem ele, um token de
 * administrador conseguiria reclamar no faucet e jogar no dado — misturando
 * a conta de operacao com a conta de usuario, o que suja qualquer auditoria
 * e abre porta para fraude dificil de rastrear.
 *
 * ATENCAO: a rota POST /api/logout fica FORA deste middleware de proposito.
 * O front-end usa o mesmo endpoint para encerrar a sessao de usuario e de
 * administrador.
 */
class EnsureAuthenticatedAsUser
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $authenticated = $request->user();

        if (! $authenticated instanceof User) {
            return response()->json([
                'message' => 'Acesso negado. Esta área é restrita a usuários.',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
