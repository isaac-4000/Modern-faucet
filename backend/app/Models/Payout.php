<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payout extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'wallet_address',
        'amount',
        'currency',
        'transaction_id',
        'status',
        'processed_at',
        'idempotency_key',
        'amount_brl',
        'points_amount',
        'error_message',
        'ip_address',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:8',
        'amount_brl' => 'decimal:8',
        'points_amount' => 'decimal:8',
        'processed_at' => 'datetime',
    ];

    /**
     * Get the user that owns the payout.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

