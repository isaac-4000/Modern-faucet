<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Cria o primeiro administrador.
 *
 * Sem este seeder o sistema tem um problema de ovo e galinha: a rota
 * POST /api/admin/admins (que cria administradores) exige que quem chama já
 * seja administrador. Ou seja, nunca existiria o primeiro.
 *
 * COMO USAR
 *   Defina no .env:
 *     ADMIN_USERNAME=seu_usuario
 *     ADMIN_EMAIL=seu@email
 *     ADMIN_PASSWORD=uma_senha_forte
 *
 *   Depois rode:
 *     php artisan migrate --seed
 *
 * Se ADMIN_PASSWORD não estiver definido, o seeder gera uma senha aleatória
 * e a mostra no terminal. ANOTE — ela não aparece de novo.
 */
class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $username = (string) (env('ADMIN_USERNAME') ?: 'admin');
        $email = env('ADMIN_EMAIL') ?: null;
        $password = env('ADMIN_PASSWORD');

        $existing = Admin::where('username', $username)->first();

        // Já existe: nunca sobrescrever a senha sozinho. Só sincroniza se o
        // .env trouxer uma senha explicitamente.
        if ($existing) {
            if (! empty($password) && ! Hash::check($password, $existing->password)) {
                $existing->update([
                    'password' => $password,
                    'email' => $email ?? $existing->email,
                ]);

                $this->command->info("Administrador \"{$username}\": senha sincronizada com o .env.");
            } else {
                $this->command->line("Administrador \"{$username}\" já existe. Nada foi alterado.");
            }

            return;
        }

        $generated = false;

        if (empty($password)) {
            $password = Str::random(24);
            $generated = true;
        }

        Admin::create([
            'username' => $username,
            'email' => $email,
            // O model Admin tem o cast 'password' => 'hashed': passamos a
            // senha em texto puro e o cast faz o hash.
            'password' => $password,
        ]);

        $this->command->info("Administrador \"{$username}\" criado.");

        if ($generated) {
            $this->command->warn('  ADMIN_PASSWORD não estava definido no .env.');
            $this->command->warn("  Senha gerada: {$password}");
            $this->command->warn('  Guarde agora — ela não será exibida novamente.');
        }
    }
}
