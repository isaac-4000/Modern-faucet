<?php

namespace Tests\Feature;

use App\Models\Payout;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Prova que as três proteções do faucet funcionam de verdade:
 *
 *   1. CAPTCHA — sem ele, um script reclama e cria contas à vontade.
 *   2. UMA CONTA POR IP — sem ele, o bot cria 500 contas e coleta em todas.
 *   3. TORNEIRA + MÍNIMO — sem eles, um ataque de bots pode esvaziar o caixa
 *      em uma noite. É o que limita o prejuízo máximo do dia.
 *
 * Rodar com:  php artisan test --filter=FaucetProtectionTest
 */
class FaucetProtectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Captcha ligado por padrão nestes testes. É o estado de produção:
        // o que queremos provar é justamente que ele barra.
        config(['services.turnstile.enabled' => true]);
        config(['services.turnstile.secret' => 'chave-secreta-de-teste']);
    }

    /**
     * Finge que a Cloudflare respondeu "humano confirmado".
     */
    private function turnstileAprova(): void
    {
        Http::fake([
            'challenges.cloudflare.com/*' => Http::response(['success' => true], 200),
        ]);
    }

    /**
     * Finge que a Cloudflare respondeu "isso é um bot".
     */
    private function turnstileReprova(): void
    {
        Http::fake([
            'challenges.cloudflare.com/*' => Http::response([
                'success' => false,
                'error-codes' => ['invalid-input-response'],
            ], 200),
        ]);
    }

    private function dadosDeCadastro(): array
    {
        return [
            'wallet_address' => 'wallet-'.bin2hex(random_bytes(8)),
            'email' => bin2hex(random_bytes(6)).'@teste.com',
            'password' => 'senha123456',
            'cf-turnstile-response' => 'token-falso',
        ];
    }

    // ------------------------------------------------------------------ captcha

    public function test_cadastro_sem_captcha_e_barrado(): void
    {
        $this->turnstileReprova();

        $dados = $this->dadosDeCadastro();
        unset($dados['cf-turnstile-response']);

        $this->postJson('/api/register', $dados)
            ->assertStatus(422);

        $this->assertSame(0, User::count());
    }

    public function test_cadastro_com_captcha_reprovado_e_barrado(): void
    {
        $this->turnstileReprova();

        $this->postJson('/api/register', $this->dadosDeCadastro())
            ->assertStatus(422);

        $this->assertSame(0, User::count());
    }

    public function test_cadastro_com_captcha_aprovado_cria_o_usuario(): void
    {
        $this->turnstileAprova();

        $this->postJson('/api/register', $this->dadosDeCadastro())
            ->assertStatus(201)
            ->assertJsonStructure(['user', 'token']);

        $this->assertSame(1, User::count());
    }

    public function test_claim_sem_captcha_nao_credita_nada(): void
    {
        $this->turnstileReprova();

        $user = User::factory()->create(['balance' => 0]);

        $this->actingAs($user)
            ->postJson('/api/faucet/claim', ['cf-turnstile-response' => 'token-falso'])
            ->assertStatus(422);

        $this->assertSame('0.00000000', (string) $user->fresh()->balance);
    }

    public function test_claim_com_captcha_credita_os_pontos_e_grava_o_ip(): void
    {
        $this->turnstileAprova();

        $user = User::factory()->create(['balance' => 0]);

        $this->actingAs($user)
            ->postJson('/api/faucet/claim', ['cf-turnstile-response' => 'token-falso'])
            ->assertStatus(200)
            ->assertJson(['amount' => 11]);

        $user->refresh();

        $this->assertSame('11.00000000', (string) $user->balance);
        $this->assertNotNull($user->last_claim_at);
        $this->assertNotNull($user->last_claim_ip);
    }

    public function test_claim_respeita_o_intervalo(): void
    {
        $this->turnstileAprova();

        $user = User::factory()->create([
            'balance' => 0,
            'last_claim_at' => now()->subSeconds(30), // menos de 4 minutos
        ]);

        $this->actingAs($user)
            ->postJson('/api/faucet/claim', ['cf-turnstile-response' => 'token-falso'])
            ->assertStatus(429);

        $this->assertSame('0.00000000', (string) $user->fresh()->balance);
    }

    // ----------------------------------------------------------- conta por IP

    public function test_quarta_conta_no_mesmo_ip_e_barrada(): void
    {
        $this->turnstileAprova();

        // O limite padrão é 3 contas por IP.
        for ($i = 1; $i <= 3; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => '177.10.20.30'])
                ->postJson('/api/register', $this->dadosDeCadastro())
                ->assertStatus(201);
        }

        $this->withServerVariables(['REMOTE_ADDR' => '177.10.20.30'])
            ->postJson('/api/register', $this->dadosDeCadastro())
            ->assertStatus(422);

        $this->assertSame(3, User::count());
    }

    public function test_outra_pessoa_em_outro_ip_consegue_se_cadastrar(): void
    {
        $this->turnstileAprova();

        for ($i = 1; $i <= 3; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => '177.10.20.30'])
                ->postJson('/api/register', $this->dadosDeCadastro())
                ->assertStatus(201);
        }

        $this->withServerVariables(['REMOTE_ADDR' => '200.99.88.77'])
            ->postJson('/api/register', $this->dadosDeCadastro())
            ->assertStatus(201);

        $this->assertSame(4, User::count());
    }

    public function test_ip_de_cadastro_fica_gravado(): void
    {
        $this->turnstileAprova();

        $this->withServerVariables(['REMOTE_ADDR' => '177.10.20.30'])
            ->postJson('/api/register', $this->dadosDeCadastro())
            ->assertStatus(201);

        $this->assertSame('177.10.20.30', User::first()->registration_ip);
    }

    public function test_limite_de_contas_por_ip_e_configuravel(): void
    {
        $this->turnstileAprova();

        Setting::setValue('max_accounts_per_ip', '1', 'Máximo de contas por IP');

        $this->withServerVariables(['REMOTE_ADDR' => '177.10.20.30'])
            ->postJson('/api/register', $this->dadosDeCadastro())
            ->assertStatus(201);

        $this->withServerVariables(['REMOTE_ADDR' => '177.10.20.30'])
            ->postJson('/api/register', $this->dadosDeCadastro())
            ->assertStatus(422);
    }

    // ------------------------------------------------- saque: mínimo e torneira

    public function test_barra_da_torneira_e_publica(): void
    {
        $this->getJson('/api/faucet/tank')
            ->assertStatus(200)
            ->assertJson([
                'currency' => 'DGB',
                'daily_cap_brl' => 20.0,
                'is_empty' => false,
            ]);
    }

    public function test_saque_abaixo_do_minimo_e_barrado(): void
    {
        Http::fake();

        // 499 pontos — um a menos que os 500 exigidos.
        $user = User::factory()->withBalance('499')->create();

        $this->actingAs($user)
            ->postJson('/api/withdraw', ['wallet_address' => 'usuario@faucetpay'])
            ->assertStatus(422)
            ->assertJson(['minimum_points' => 500.0]);

        $this->assertSame('499.00000000', (string) $user->fresh()->balance);
        $this->assertSame(0, Payout::count());
    }

    public function test_saque_com_torneira_vazia_e_barrado(): void
    {
        Http::fake();

        // Teto de R$ 20, e já saiu mais do que isso hoje.
        Setting::setValue('faucetpay_daily_cap_brl', '20.00', 'Teto diário em reais');

        Payout::create([
            'user_id' => User::factory()->create()->id,
            'wallet_address' => 'outro@faucetpay',
            'amount' => '10.00000000',
            'currency' => 'DGB',
            'amount_brl' => '25.00',
            'points_amount' => '7000',
            'status' => 'completed',
        ]);

        $user = User::factory()->withBalance('1000')->create();

        $this->actingAs($user)
            ->postJson('/api/withdraw', ['wallet_address' => 'usuario@faucetpay'])
            ->assertStatus(503)
            ->assertJsonPath('tank.is_empty', true);

        // O saldo do usuário continua intacto.
        $this->assertSame('1000.00000000', (string) $user->fresh()->balance);
    }

    public function test_saque_nao_paga_mais_do_que_sobrou_na_torneira(): void
    {
        Http::fake([
            'faucetpay.io/*' => Http::response([
                'status' => 200,
                'payout_id' => 'fp-123456',
            ], 200),
        ]);

        // Teto de R$ 1,00. 1 ponto = US$ 0,00001 x R$ 5,40 = R$ 0,000054.
        // R$ 1,00 / R$ 0,000054 = ~18.518 pontos.
        Setting::setValue('faucetpay_daily_cap_brl', '1.00', 'Teto diário em reais');

        // O usuário tem 100.000 pontos, muito mais do que o tanque permite.
        $user = User::factory()->withBalance('100000')->create();

        $this->actingAs($user)
            ->postJson('/api/withdraw', ['wallet_address' => 'usuario@faucetpay'])
            ->assertStatus(200);

        $payout = Payout::first();

        // O valor em reais nunca pode passar do teto.
        $this->assertLessThanOrEqual(1.0, (float) $payout->amount_brl);

        // E o resto dos pontos continua na conta do usuário.
        $this->assertGreaterThan(80000.0, (float) $user->fresh()->balance);
    }

    public function test_saque_aprovado_debita_o_saldo_e_guarda_o_valor_em_reais(): void
    {
        Http::fake([
            'faucetpay.io/*' => Http::response([
                'status' => 200,
                'payout_id' => 'fp-999',
            ], 200),
        ]);

        $user = User::factory()->withBalance('1000')->create();

        $this->actingAs($user)
            ->postJson('/api/withdraw', ['wallet_address' => 'usuario@faucetpay'])
            ->assertStatus(200)
            ->assertJson(['currency' => 'DGB']);

        // 1000 pontos = US$ 0,01 = R$ 0,054
        $this->assertSame('0.00000000', (string) $user->fresh()->balance);

        $payout = Payout::first();
        $this->assertSame('completed', $payout->status);
        $this->assertSame('fp-999', $payout->transaction_id);
        $this->assertEqualsWithDelta(0.054, (float) $payout->amount_brl, 0.001);
        $this->assertNotEmpty($payout->idempotency_key);
    }

    public function test_saque_que_falha_devolve_o_saldo_do_usuario(): void
    {
        Http::fake([
            'faucetpay.io/*' => Http::response([
                'status' => 429,
                'message' => 'Daily payout limit reached',
            ], 200),
        ]);

        $user = User::factory()->withBalance('1000')->create();

        $this->actingAs($user)
            ->postJson('/api/withdraw', ['wallet_address' => 'usuario@faucetpay'])
            ->assertStatus(502);

        // O mais importante deste teste: o dinheiro volta.
        $this->assertSame('1000.00000000', (string) $user->fresh()->balance);

        $payout = Payout::first();
        $this->assertSame('failed', $payout->status);
        $this->assertStringContainsString('Daily payout limit', $payout->error_message);
    }

    public function test_saque_exige_login(): void
    {
        $this->postJson('/api/withdraw', ['wallet_address' => 'usuario@faucetpay'])
            ->assertStatus(401);
    }

    // ---------------------------------------------------------- a matemática

    public function test_valor_do_claim_fica_preso_no_dolar_e_nao_na_moeda(): void
    {
        $economy = app(\App\Services\FaucetEconomy::class);

        // US$ 0,00010865 por claim, 1 ponto = US$ 0,00001 -> 10,865 -> 11 pontos.
        $this->assertSame(11.0, $economy->recalculateClaimAmount());

        // O DGB dobra de preço. Nada muda nos pontos — e é esse o ponto:
        // o que você paga em dólar continua igual. Só o valor em DGB cai.
        Setting::setValue('dgb_usd_price', '0.00698', 'Preço do DGB');

        $this->assertSame(11.0, $economy->recalculateClaimAmount());
        $this->assertSame('0.01575931', $economy->cryptoFromPoints(11.0));
    }

    public function test_claim_em_dgb_cai_quando_a_moeda_valoriza(): void
    {
        $economy = app(\App\Services\FaucetEconomy::class);

        // DGB a US$ 0,00349 -> 11 pontos = 0,0315 DGB
        $this->assertSame('0.03151862', $economy->cryptoFromPoints(11.0));

        Setting::setValue('dgb_usd_price', '0.00698', 'Preço do DGB');

        // Dobra o preço, cai pela metade o DGB pago. Custo em dólar: igual.
        $this->assertSame('0.01575931', $economy->cryptoFromPoints(11.0));
    }

    public function test_minimo_de_saque_em_dgb_deriva_dos_pontos(): void
    {
        $economy = app(\App\Services\FaucetEconomy::class);

        // 500 pontos = US$ 0,005. A US$ 0,00349 por DGB = ~1,43 DGB.
        $this->assertSame('1.43266476', $economy->minimumPayoutCrypto());
    }
}
