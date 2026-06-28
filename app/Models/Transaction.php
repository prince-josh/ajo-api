<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'wallet_id',
        'user_id',
        'reference',
        'type',
        'category',
        'amount',
        'balance_before',
        'balance_after',
        'status',
        'paystack_reference',
        'description',
        'metadata',
    ];

    protected $casts = [
        'amount'         => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after'  => 'decimal:2',
        'metadata'       => 'array',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function contribution()
    {
        return $this->hasOne(Contribution::class);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    public static function generateReference(): string
    {
        return 'AJO-' . strtoupper(uniqid()) . '-' . now()->format('YmdHis');
    }
}
