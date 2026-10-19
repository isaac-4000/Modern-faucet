<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Prova que um usuário comum não consegue usar as rotas de administrador.
 *
 * Estes testes existem por causa de uma falha real encontrada em 18/10/2026:
 * as rotas /api/admin/* usavam apenas auth:sanctum, que aceita qualquer token
 * válido — inclusive o de um usuário comum. Qualquer pessoa logada conseguia
 * alterar o próprio saldo e criar administradores.
 *
 * Rodar com:  php artisan test --filter=AdminAuthorizationTest
 */
class AdminAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Cria um usuário comum e devolve o token dele.
     */
    private function userToken(): string
    {
        $user = User::factory()->create();

        return $user->createToken('auth_token')->plainTextToken;
    }

    /**
     * Cria um administrador e devolve um token COM a habilidade "admin".
     */
    private function adminToken(): string
    {
        $admin = Admin::create([
            'username' => 'admin-teste',
            'email' => 'admin-teste@example.com',
            'password' => 'senha-super-forte-123',
        ]);

        return $admin->createToken('admin_token', ['admin'])->plainTextToken;
    }

    // ---------------------------------------------------------------------
    // O ATAQUE: usuário comum tentando agir como administrador
    // ---------------------------------------------------------------------

    public function test_usuario_comum_nao_abre_o_painel_admin(): void
    {
        $this->withHeader('Authorization', 'Bearer '.$this->userToken())
            ->getJson('/api/admin/dashboard')
            ->assertForbidden();
    }

    public function test_usuario_comum_nao_lista_os_usuarios(): void
    {
        $this->withHeader('Authorization', 'Bearer '.$this->userToken())
            ->getJson('/api/admin/users')
            ->assertForbidden();
    }

    public function test_usuario_comum_nao_altera_o_proprio_saldo(): void
    {
        $user = User::factory()->withBalance('0.00000010')->create();
        $token = $user->createToken('auth_token')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/admin/users/'.$user->id.'/balance', ['balance' => 9999])
            ->assertForbidden();

        // O saldo não pode ter mudado nem um centésimo de satoshi.
        $this->assertSame('0.00000010', (string) $user->fresh()->balance);
    }

    public function test_usuario_comum_nao_cria_administrador(): void
    {
        $this->withHeader('Authorization', 'Bearer '.$this->userToken())
            ->postJson('/api/admin/admins', [
                'username' => 'invasor',
                'password' => 'senha-super-forte-123',
            ])
            ->assertForbidden();

        $this->assertSame(0, Admin::count());
    }

    public function test_usuario_comum_nao_muda_as_configuracoes(): void
    {
        $this->withHeader('Authorization', 'Bearer '.$this->userToken())
            ->putJson('/api/admin/settings/1', ['setting_value' => '99'])
            ->assertForbidden();
    }

    // ---------------------------------------------------------------------
    // O CAMINHO DO BEM: o administrador consegue
    // ---------------------------------------------------------------------

    public function test_administrador_abre_o_painel(): void
    {
        $this->withHeader('Authorization', 'Bearer '.$this->adminToken())
            ->getJson('/api/admin/dashboard')
            ->assertOk();
    }

    public function test_administrador_lista_os_usuarios(): void
    {
        $this->withHeader('Authorization', 'Bearer '.$this->adminToken())
            ->getJson('/api/admin/users')
            ->assertOk();
    }

    // ---------------------------------------------------------------------
    // SEPARAÇÃO: token de admin não vale como usuário e vice-versa
    // ---------------------------------------------------------------------

    public function test_token_de_admin_nao_reclama_no_faucet(): void
    {
        $this->withHeader('Authorization', 'Bearer '.$this->adminToken())
            ->postJson('/api/faucet/claim')
            ->assertForbidden();
    }

    public function test_token_de_usuario_abre_a_propria_conta(): void
    {
        $this->withHeader('Authorization', 'Bearer '.$this->userToken())
            ->getJson('/api/user')
            ->assertOk();
    }

    // ---------------------------------------------------------------------
    // A SEGUNDA CAMADA: ser admin não basta, o token precisa da permissão
    // ---------------------------------------------------------------------

    public function test_token_de_admin_sem_habilidade_admin_e_recusado(): void
    {
        $admin = Admin::create([
            'username' => 'admin-sem-permissao',
            'email' => 'sem-permissao@example.com',
            'password' => 'senha-super-forte-123',
        ]);

        // Token criado SEM a habilidade "admin".
        $token = $admin->createToken('token_fraco')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/admin/dashboard')
            ->assertForbidden();
    }

    // ---------------------------------------------------------------------
    // BÁSICO: sem token não entra em nada
    // ---------------------------------------------------------------------

    public function test_sem_token_nao_entra_em_nada(): void
    {
        $this->getJson('/api/admin/dashboard')->assertUnauthorized();
        $this->getJson('/api/user')->assertUnauthorized();
    }
}
