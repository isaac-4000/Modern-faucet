<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create("users", function (Blueprint $table) {
            $table->id();
            $table->string("wallet_address")->unique();
            $table->string("email")->unique()->nullable();
            $table->string("password");
            $table->decimal("balance", 18, 8)->default(0.00000000);
            $table->string("referral_code", 50)->unique()->nullable();
            $table->string("referred_by")->nullable();
            $table->timestamp("last_claim_at")->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("users");
    }
};


