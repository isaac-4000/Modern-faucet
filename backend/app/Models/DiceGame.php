<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiceGame extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'bet_amount',
        'roll_result',
        'target_number',
        'is_win',
        'payout_amount',
        'server_seed',
        'client_seed',
        'nonce',
        'hashed_server_seed',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'bet_amount' => 'decimal:8',
        'roll_result' => 'decimal:2',
        'target_number' => 'decimal:2',
        'payout_amount' => 'decimal:8',
        'is_win' => 'boolean',
    ];

    /**
     * Get the user that owns the dice game.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Generate a provably fair roll result.
     */
    public static function generateRoll($serverSeed, $clientSeed, $nonce)
    {
        $hash = hash('sha256', $serverSeed . ':' . $clientSeed . ':' . $nonce);
        $hex = substr($hash, 0, 8);
        $decimal = hexdec($hex);
        return ($decimal % 10000) / 100;
    }

    /**
     * Verify the roll result.
     */
    public function verifyRoll()
    {
        $expectedRoll = self::generateRoll($this->server_seed, $this->client_seed, $this->nonce);
        return abs($expectedRoll - $this->roll_result) < 0.01;
    }
}

