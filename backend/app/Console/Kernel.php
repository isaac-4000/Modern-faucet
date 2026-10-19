<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();

        // Mantém o custo por claim preso no dólar, não na moeda.
        // Se o DGB subir e ninguém recalcular, o seu custo sobe calado.
        // Toda segunda às 6h da manhã.
        //
        // Para isso rodar, o servidor precisa ter este cron:
        //   * * * * * cd /caminho/do/projeto && php artisan schedule:run >> /dev/null 2>&1
        $schedule->command('faucet:recalculate-claim')
            ->weeklyOn(1, '06:00')
            ->withoutOverlapping()
            ->onFailure(function () {
                \Illuminate\Support\Facades\Log::error('faucet.recalculo_falhou');
            });
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
