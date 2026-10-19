<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Valores iniciais da economia do faucet.
 *
 * Nada aqui é definitivo: todos esses números são editáveis no painel do
 * admin (Configurações), sem precisar mexer em código.
 *
 * De onde veio cada número:
 *  - 4 minutos e 11 pontos/claim  → calibrados na régua do FreeBitco.in no
 *    fim da vida (~2 sats/hora = 48 sats/dia = US$ 0,03880/dia), que foi a
 *    referência que você escolheu.
 *  - 500 pontos de mínimo         → a FaucetPay cobra 0,25 DGB por saque;
 *    abaixo disso a taxa come mais da metade do pagamento.
 *  - R$ 20 por dia                → o teto que você topa perder num dia ruim.
 *
 * Um ponto vale US$ 0,00001. É a âncora: se o DGB dobrar de preço, o custo
 * por claim em dólar continua o mesmo e o valor em DGB cai sozinho.
 */
class SettingsSeeder extends Seeder
{
    /**
     * Chave => [valor, descrição]
     */
    public const SETTINGS = [
        // ------------------------------------------------------- a âncora
        'usd_per_point' => [
            '0.00001',
            'Quanto vale 1 ponto, em dólar. Toda a economia é calculada em cima disso.',
        ],
        'usd_per_claim' => [
            '0.00010865',
            'Quanto você paga por claim, em dólar. 15 claims/hora x 24h = US$ 0,039/dia por usuário.',
        ],
        'dgb_usd_price' => [
            '0.00349',
            'Preço do DGB em dólar. Atualize quando a moeda oscilar.',
        ],
        'usd_brl_rate' => [
            '5.40',
            'Cotação do dólar em reais. Usada só para o teto diário.',
        ],
        'payout_currency' => [
            'DGB',
            'Moeda de pagamento. Troque aqui se o DGB der problema (plano B: LTC).',
        ],

        // -------------------------------------------------- o dia a dia
        'faucet_claim_amount' => [
            '11',
            'Pontos ganhos por claim. Recalculado a partir de usd_per_claim.',
        ],
        'faucet_claim_interval_seconds' => [
            '240',
            'Tempo em segundos entre claims (240 = 4 minutos).',
        ],
        'referral_percentage' => [
            '0.10',
            'Fração do claim que vai para quem indicou (0.10 = 10%).',
        ],

        // ------------------------------------------------------- os limites
        'payout_min_points' => [
            '500',
            'Mínimo de pontos para sacar (500 = US$ 0,005 = ~1,43 DGB).',
        ],
        'faucetpay_daily_cap_brl' => [
            '20.00',
            'TETO DIÁRIO DE SAQUES EM REAIS. O que passar disso não sai, aconteça o que acontecer.',
        ],
        'max_accounts_per_ip' => [
            '3',
            'Máximo de contas criadas no mesmo IP. Suba se usuários reais forem bloqueados (CGNAT).',
        ],
    ];

    public function run(): void
    {
        foreach (self::SETTINGS as $key => [$value, $description]) {
            // firstOrCreate: nunca sobrescreve um valor que você já ajustou
            // no painel. Rodar o seeder de novo é seguro.
            Setting::firstOrCreate(
                ['setting_key' => $key],
                ['setting_value' => $value, 'description' => $description]
            );
        }
    }
}
