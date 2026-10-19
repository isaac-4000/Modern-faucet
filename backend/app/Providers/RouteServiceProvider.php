<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        /*
         * Limites específicos de autenticação.
         *
         * O limite genérico "api" (60/min) é alto demais para rotas de login:
         * ele existe para não atrapalhar o uso normal do site, não para
         * proteger senha. Aqui os limites são baixos e, no caso de login,
         * a chave combina o identificador com o IP — assim um atacante não
         * consegue travar a conta de um usuário só por estar no mesmo IP.
         */

        // Cadastro: 10 por minuto por IP.
        // ATENÇÃO: no Brasil muitas operadoras usam CGNAT (vários clientes
        // atrás do mesmo IP). Se usuários legítimos forem bloqueados, suba
        // este número — mas mantenha algum limite, porque criação em massa
        // de contas é o ataque mais comum contra faucet.
        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        // Login de usuário: 10 por minuto por (carteira + IP).
        RateLimiter::for('login', function (Request $request) {
            $identifier = strtolower((string) $request->input('wallet_address'));

            return Limit::perMinute(10)->by($identifier.'|'.$request->ip());
        });

        // Login de administrador: 5 por minuto por (usuário + IP).
        // Aqui pode ser rigoroso: é uma conta só, e é a porta do cofre.
        RateLimiter::for('admin-login', function (Request $request) {
            $identifier = strtolower((string) $request->input('username'));

            return Limit::perMinute(5)->by($identifier.'|'.$request->ip());
        });

        /*
         * Claim do faucet.
         *
         * O intervalo de 4 minutos já é o limitador de velocidade — ninguém
         * reclama mais de 15 vezes por hora. Estes limites são a rede de
         * segurança para quem tentar burlar o relógio ou rodar várias contas
         * no mesmo IP.
         *
         * O limite por IP (60/hora) tolera até 4 contas no teto do intervalo.
         * foi escolhido pensando em CGNAT: várias pessoas reais dividem o
         * mesmo IP nas operadoras brasileiras. Se mesmo assim bloquear gente
         * legítima, suba este número.
         */
        RateLimiter::for('claim', function (Request $request) {
            $userId = $request->user()?->id ?? $request->ip();

            return [
                Limit::perMinute(5)->by('claim-user-'.$userId),
                Limit::perHour(60)->by('claim-ip-'.$request->ip()),
            ];
        });

        // Saque: 3 pedidos por hora por usuário. Um humano não precisa de mais.
        RateLimiter::for('withdraw', function (Request $request) {
            $userId = $request->user()?->id ?? $request->ip();

            return Limit::perHour(3)->by('withdraw-'.$userId);
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
