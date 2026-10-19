<?php

namespace App\Services;

use App\Models\Payout;
use App\Models\Setting;
use Illuminate\Support\Carbon;

/**
 * Toda a matemática do faucet vive aqui — em um lugar só, de propósito.
 *
 * A REGRA DE OURO (plano de negócio, seção 3.4):
 *
 *      A unidade interna é o PONTO.  1 ponto = US$ 0,00001.  Sempre.
 *
 * O DGB entra apenas na hora de pagar. Assim, se o DGB dobrar de preço
 * amanhã, o que você paga por claim continua sendo os mesmos 11 pontos —
 * o seu custo em dólar não muda. O usuário também não sofre: a carteira dele
 * não encolhe quando a moeda cai.
 *
 * Tudo é lido da tabela settings. Nada está cravado no código: intervalo,
 * valor do claim, cotação, mínimo e teto diário são editáveis no painel do
 * admin, sem deploy.
 */
class FaucetEconomy
{
    // ---------------------------------------------------------------- leituras

    /**
     * Quanto vale um ponto, em dólar. A âncora de tudo.
     */
    public function usdPerPoint(): float
    {
        return (float) Setting::getValue('usd_per_point', '0.00001');
    }

    /**
     * Quanto você quer pagar por claim, em dólar.
     *
     * Vem da régua do usuário: o FreeBitco.in pagava ~2 sats/hora
     * (= 48 sats/dia = US$ 0,03880/dia) no fim da vida. A 15 claims por hora
     * (intervalo de 4 minutos), isso dá US$ 0,00010865 por claim.
     */
    public function usdPerClaim(): float
    {
        return (float) Setting::getValue('usd_per_claim', '0.00010865');
    }

    /**
     * Preço do DGB em dólar. Atualize quando a moeda oscilar.
     */
    public function dgbUsdPrice(): float
    {
        return (float) Setting::getValue('dgb_usd_price', '0.00349');
    }

    /**
     * Dólar em reais. Usado só para o teto diário, que é pensado em reais.
     */
    public function usdBrlRate(): float
    {
        return (float) Setting::getValue('usd_brl_rate', '5.40');
    }

    public function payoutCurrency(): string
    {
        return strtoupper((string) Setting::getValue('payout_currency', 'DGB'));
    }

    public function claimIntervalSeconds(): int
    {
        return (int) Setting::getValue('faucet_claim_interval_seconds', '240');
    }

    // ----------------------------------------------------------- o valor base

    /**
     * Quantos pontos o usuário ganha por claim.
     *
     * Guardado no banco em pontos (e não em DGB) justamente para o valor que
     * o usuário vê não ficar mudando junto com a cotação.
     */
    public function claimAmountPoints(): float
    {
        return (float) Setting::getValue('faucet_claim_amount', '11');
    }

    // ------------------------------------------------------------ conversões

    public function usdFromPoints(float $points): float
    {
        return $points * $this->usdPerPoint();
    }

    public function brlFromPoints(float $points): float
    {
        return $this->usdFromPoints($points) * $this->usdBrlRate();
    }

    public function pointsFromBrl(float $brl): float
    {
        $perPoint = $this->usdPerPoint() * $this->usdBrlRate();

        return $perPoint > 0 ? $brl / $perPoint : 0.0;
    }

    /**
     * Converte pontos para a moeda de pagamento, com 8 casas decimais.
     */
    public function cryptoFromPoints(float $points): string
    {
        $price = $this->dgbUsdPrice();

        if ($price <= 0) {
            return '0.00000000';
        }

        $usd = $this->usdFromPoints($points);

        return number_format($usd / $price, 8, '.', '');
    }

    // ---------------------------------------------------------------- saque

    /**
     * Mínimo de saque, em pontos. 500 pontos = US$ 0,005 = ~1,43 DGB.
     *
     * Existe por um motivo prático: a FaucetPay cobra 0,25 DGB de taxa fixa
     * por saque. Abaixo desse valor, a taxa come mais da metade do pagamento.
     */
    public function minimumPayoutPoints(): float
    {
        return (float) Setting::getValue('payout_min_points', '500');
    }

    /**
     * O mesmo mínimo, na moeda de pagamento — só para exibir na tela.
     */
    public function minimumPayoutCrypto(): string
    {
        return $this->cryptoFromPoints($this->minimumPayoutPoints());
    }

    // ------------------------------------------------- a torneira (teto diário)

    /**
     * Teto diário de saques, em reais. É a trava que limita o prejuízo.
     */
    public function dailyCapBrl(): float
    {
        return (float) Setting::getValue('faucetpay_daily_cap_brl', '20.00');
    }

    /**
     * Quanto já saiu hoje, em reais.
     *
     * Usa o valor congelado em amount_brl (e não a cotação de agora) porque o
     * teto precisa ser estável: se o DGB subir durante o dia, o que já foi
     * pago não pode passar a valer mais e estourar o teto retroativamente.
     */
    public function paidTodayBrl(): float
    {
        return (float) Payout::query()
            ->whereIn('status', ['pending', 'completed'])
            ->whereBetween('created_at', [
                Carbon::today()->startOfDay(),
                Carbon::today()->endOfDay(),
            ])
            ->sum('amount_brl');
    }

    public function remainingBrl(): float
    {
        return max(0.0, $this->dailyCapBrl() - $this->paidTodayBrl());
    }

    public function isTankEmpty(): bool
    {
        return $this->remainingBrl() <= 0.0;
    }

    /**
     * O estado da torneira — é isso que alimenta a barra na tela.
     */
    public function tank(): array
    {
        $cap = $this->dailyCapBrl();
        $paid = $this->paidTodayBrl();
        $remaining = $this->remainingBrl();

        $percentUsed = $cap > 0 ? min(100.0, round(($paid / $cap) * 100, 2)) : 100.0;

        $payoutsToday = Payout::query()
            ->whereIn('status', ['pending', 'completed'])
            ->whereBetween('created_at', [
                Carbon::today()->startOfDay(),
                Carbon::today()->endOfDay(),
            ])
            ->count();

        return [
            'currency' => $this->payoutCurrency(),
            'daily_cap_brl' => round($cap, 2),
            'paid_today_brl' => round($paid, 2),
            'remaining_brl' => round($remaining, 2),
            'percent_used' => $percentUsed,
            'percent_remaining' => round(100.0 - $percentUsed, 2),
            'is_empty' => $remaining <= 0.0,
            'payouts_today' => $payoutsToday,
            'dgb_usd_price' => $this->dgbUsdPrice(),
            'usd_brl_rate' => $this->usdBrlRate(),
            'usd_per_claim' => $this->usdPerClaim(),
            'usd_per_point' => $this->usdPerPoint(),
            'claim_amount_points' => $this->claimAmountPoints(),
            'claim_interval_seconds' => $this->claimIntervalSeconds(),
            'minimum_payout_points' => $this->minimumPayoutPoints(),
            'minimum_payout_crypto' => $this->minimumPayoutCrypto(),
        ];
    }

    // ------------------------------------------------------------- manutenção

    /**
     * Recalcula quantos pontos vale um claim, a partir da âncora em dólar.
     *
     * Roda quando o DGB oscilar. É o que impede o custo de subir calado.
     * Arredonda para o ponto inteiro mais próximo: o número que o usuário vê
     * na tela fica redondo, e a diferença fica dentro da margem.
     */
    public function recalculateClaimAmount(): float
    {
        $perPoint = $this->usdPerPoint();

        if ($perPoint <= 0) {
            return $this->claimAmountPoints();
        }

        $points = max(1.0, round($this->usdPerClaim() / $perPoint));

        Setting::setValue(
            'faucet_claim_amount',
            (string) $points,
            'Pontos ganhos por claim (recalculado a partir de usd_per_claim)'
        );

        return $points;
    }
}
