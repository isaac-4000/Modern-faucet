<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * ATENÇÃO: esta factory foi reescrita para refletir a tabela `users` real
     * do projeto (wallet_address, balance, referral_code...). A factory padrão
     * do Laravel usa `name` e `email_verified_at`, que não existem aqui — por
     * isso qualquer teste que chamasse User::factory() estourava em SQL.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'wallet_address' => 'wallet-'.Str::lower(Str::random(20)),
            'email' => fake()->unique()->safeEmail(),
            // O model User tem o cast 'password' => 'hashed': passamos a senha
            // em texto puro e deixamos o cast fazer o hash.
            'password' => static::$password ??= 'password',
            'balance' => 0,
            'referral_code' => Str::random(10),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model should have a specific balance.
     */
    public function withBalance(string $balance): static
    {
        return $this->state(fn (array $attributes) => [
            'balance' => $balance,
        ]);
    }
}
