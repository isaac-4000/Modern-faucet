<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Campos que o saque via FaucetPay precisa:
     *
     *  - idempotency_key: enviado para a API. Se o servidor repetir a
     *    chamada, a FaucetPay não paga duas vezes.
     *  - amount_brl: valor congelado em reais no momento do saque. É ele
     *    que alimenta o teto diário — sem isso o teto dependeria da cotação
     *    do dia e o controle furaria.
     *  - points_amount: quanto valia em pontos, para auditoria.
     *  - error_message: por que o saque falhou, quando falhar.
     *  - ip_address: de onde veio o pedido.
     */
    public function up(): void
    {
        Schema::table('payouts', function (Blueprint $table) {
            $table->string('idempotency_key', 64)->nullable()->unique()->after('currency');
            $table->decimal('amount_brl', 18, 8)->nullable()->after('amount');
            $table->decimal('points_amount', 18, 8)->nullable()->after('amount_brl');
            $table->text('error_message')->nullable()->after('status');
            $table->string('ip_address', 45)->nullable()->after('error_message');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payouts', function (Blueprint $table) {
            $table->dropUnique(['idempotency_key']);
            $table->dropColumn([
                'idempotency_key',
                'amount_brl',
                'points_amount',
                'error_message',
                'ip_address',
            ]);
        });
    }
};
