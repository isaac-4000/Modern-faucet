<?php

namespace App\Console\Commands;

use App\Services\FaucetEconomy;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Recalcula quantos pontos vale um claim, a partir do preço atual do DGB.
 *
 * Por que isso existe:
 *   O dólar é a âncora, não a moeda. Se o DGB subir 50% e você não fizer
 *   nada, cada claim passa a custar 50% mais em dólar — e você só vai notar
 *   quando o dinheiro acabar. Este comando impede isso.
 *
 * Como rodar:
 *   php artisan faucet:recalculate-claim
 *
 * O agendamento já está registrado em app/Console/Kernel.php (toda segunda,
 * 6h da manhã). Para o agendamento funcionar, o servidor precisa ter este
 * cron:
 *   * * * * * cd /caminho/do/projeto && php artisan schedule:run >> /dev/null 2>&1
 */
class RecalculateClaimAmount extends Command
{
    protected $signature = 'faucet:recalculate-claim
                            {--dry-run : Mostra o que mudaria, sem gravar nada}';

    protected $description = 'Recalcula o valor do claim em pontos a partir do preço do DGB';

    public function handle(FaucetEconomy $economy): int
    {
        $before = $economy->claimAmountPoints();
        $price = $economy->dgbUsdPrice();
        $usdPerClaim = $economy->usdPerClaim();

        if ($price <= 0) {
            $this->error('Preço do DGB (dgb_usd_price) está zerado. Nada foi feito.');

            return self::FAILURE;
        }

        if ($this->option('dry-run')) {
            $this->line(sprintf(
                'Hoje: %s pontos | alvo: US$ %.8f/claim | DGB: US$ %.8f | passaria para %s pontos',
                $before,
                $usdPerClaim,
                $price,
                max(1.0, round($usdPerClaim / $economy->usdPerPoint()))
            ));

            return self::SUCCESS;
        }

        $after = $economy->recalculateClaimAmount();

        if ((float) $before === (float) $after) {
            $this->info(sprintf('Nada a mudar: %s pontos já está certo.', $after));

            return self::SUCCESS;
        }

        Log::info('faucet.claim_recalculado_automatico', [
            'de' => $before,
            'para' => $after,
            'dgb_usd_price' => $price,
            'usd_per_claim' => $usdPerClaim,
        ]);

        $this->info(sprintf(
            'Claim recalculado: %s -> %s pontos (DGB a US$ %.8f).',
            $before,
            $after,
            $price
        ));

        return self::SUCCESS;
    }
}
