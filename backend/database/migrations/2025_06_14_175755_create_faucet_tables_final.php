<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create("admins", function (Blueprint $table) {
            $table->id();
            $table->string("username")->unique();
            $table->string("password");
            $table->string("email")->unique()->nullable();
            $table->timestamps();
        });

        Schema::create("transactions", function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("user_id");
            $table->enum("type", ["faucet_claim", "dice_win", "dice_loss", "referral_bonus", "withdrawal"]);
            $table->decimal("amount", 18, 8);
            $table->string("currency", 10)->default("BTC");
            $table->string("transaction_hash")->unique()->nullable();
            $table->enum("status", ["pending", "completed", "failed"])->default("pending");
            $table->timestamps();
            $table->foreign("user_id")->references("id")->on("users");
        });

        Schema::create("dice_games", function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("user_id");
            $table->decimal("bet_amount", 18, 8);
            $table->decimal("roll_result", 5, 2);
            $table->decimal("target_number", 5, 2);
            $table->boolean("is_win");
            $table->decimal("payout_amount", 18, 8);
            $table->string("server_seed");
            $table->string("client_seed");
            $table->integer("nonce");
            $table->string("hashed_server_seed");
            $table->timestamps();
            $table->foreign("user_id")->references("id")->on("users");
        });

        Schema::create("payouts", function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("user_id");
            $table->string("wallet_address");
            $table->decimal("amount", 18, 8);
            $table->string("currency", 10)->default("BTC");
            $table->string("transaction_id")->unique()->nullable();
            $table->enum("status", ["pending", "completed", "failed"])->default("pending");
            $table->timestamp("processed_at")->nullable();
            $table->timestamps();
            $table->foreign("user_id")->references("id")->on("users");
        });

        Schema::create("settings", function (Blueprint $table) {
            $table->id();
            $table->string("setting_key")->unique();
            $table->text("setting_value")->nullable();
            $table->text("description")->nullable();
            $table->timestamps();
        });

        DB::table("settings")->insert([
            ["setting_key" => "faucet_claim_amount", "setting_value" => "0.00000010", "description" => "Quantidade de BTC por claim da faucet"],
            ["setting_key" => "faucet_claim_interval_seconds", "setting_value" => "1800", "description" => "Intervalo em segundos entre claims da faucet (30 minutos)"],
            ["setting_key" => "referral_percentage", "setting_value" => "0.10", "description" => "Porcentagem de comissão por referência"],
            ["setting_key" => "min_withdrawal_amount", "setting_value" => "0.00001000", "description" => "Valor mínimo para saque"],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("settings");
        Schema::dropIfExists("payouts");
        Schema::dropIfExists("dice_games");
        Schema::dropIfExists("transactions");
        Schema::dropIfExists("admins");
    }
};


