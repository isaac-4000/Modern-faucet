<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'wallet_address',
        'email',
        'password',
        'balance',
        'referral_code',
        'referred_by',
        'last_claim_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'last_claim_at' => 'datetime',
        'password' => 'hashed',
        'balance' => 'decimal:8',
    ];

    /**
     * Get the transactions for the user.
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get the dice games for the user.
     */
    public function diceGames()
    {
        return $this->hasMany(DiceGame::class);
    }

    /**
     * Get the payouts for the user.
     */
    public function payouts()
    {
        return $this->hasMany(Payout::class);
    }

    /**
     * Check if user can claim from faucet.
     */
    public function canClaim()
    {
        if (!$this->last_claim_at) {
            return true;
        }

        $setting = Setting::where('setting_key', 'faucet_claim_interval_seconds')->first();
        $interval = $setting ? (int)$setting->setting_value : 1800; // Default 30 minutes

        return $this->last_claim_at->addSeconds($interval)->isPast();
    }

    /**
     * Get the user's referral URL.
     */
    public function getReferralUrlAttribute()
    {
        return url('/register?ref=' . $this->referral_code);
    }
}

