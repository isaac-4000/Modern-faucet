<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Guarda o IP de cadastro, de login e do último claim.
     *
     * Sem o IP de cadastro não existe como aplicar a regra "uma conta por IP"
     * que é a defesa contra criação de contas em massa (o ataque mais comum
     * contra faucet).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('registration_ip', 45)->nullable()->after('referred_by');
            $table->string('last_login_ip', 45)->nullable()->after('registration_ip');
            $table->string('last_claim_ip', 45)->nullable()->after('last_claim_at');

            // Índice: a checagem de "quantas contas já saíram deste IP"
            // roda em todo cadastro.
            $table->index('registration_ip', 'users_registration_ip_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_registration_ip_index');
            $table->dropColumn([
                'registration_ip',
                'last_login_ip',
                'last_claim_ip',
            ]);
        });
    }
};
