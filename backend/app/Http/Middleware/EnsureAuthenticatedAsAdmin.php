<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Garante que o token Sanctum usado na requisicao pertence a um ADMINISTRADOR.
 *
 * POR QUE ESTE MIDDLEWARE EXISTE
 * -----------------------------
 * O middleware auth:sanctum apenas responde "este token e valido?". Ele nao
 * responde "valido PARA QUEM?". Como User e Admin sao models diferentes e os
 * dois emitem tokens Sanctum, um usuario comum autenticado conseguia chamar
 * qualquer rota /api/admin/* — incluindo alterar o proprio saldo e criar
 * outros administradores.
 *
 * Este middleware fecha essa porta em duas camadas:
 *   1. Identidade: o dono do token TEM que ser um registro da tabela admins.
 *   2. Permissao: o token TEM que carregar a habilidade "admin".
 *
 * Se qualquer uma das duas falhar, a requisicao termina em 403.
 */
class EnsureAuthenticatedAsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $authenticated = $request->user();

        // Camada 1 — o token precisa pertencer a um administrador.
        if (! $authenticated instanceof Admin) {
            return response()->json([
                'message' => 'Acesso negado. Esta área é restrita a administradores.',
            ], Response::HTTP_FORBIDDEN);
        }

        // Camada 2 — o token precisa ter a habilidade "admin".
        // tokenCan() devolve false quando nao existe token no request,
        // ou seja, fecha a porta por padrao em vez de liberar.
        if (! $authenticated->tokenCan('admin')) {
            return response()->json([
                'message' => 'Acesso negado. Este token não tem permissão de administrador.',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
