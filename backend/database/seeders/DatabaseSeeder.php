<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        // Cria o primeiro administrador (leia database/seeders/AdminSeeder.php).
        // Sem ele não existe como logar no painel: a rota que cria admins
        // exige que quem chama já seja admin.
        $this->call(AdminSeeder::class);
    }
}
