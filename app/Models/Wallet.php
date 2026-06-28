<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    protected $fillable = [
        'user_id',
        'balance',
        'total_saved',
        'total_withdrawn',
        'is_locked',
    ];

    protected $casts = [
        'balance'          => 'decimal:2',
        'total_saved'      => 'decimal:2',
        'total_withdrawn'  => 'decimal:2',
        'is_locked'        => 'boolean',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    public function credit(float $amount): void
    {
        $this->increment('balance', $amount);
        $this->increment('total_saved', $amount);
    }

    public function debit(float $amount): void
    {
        $this->decrement('balance', $amount);
        $this->increment('total_withdrawn', $amount);
    }

    public function hasSufficientBalance(float $amount): bool
    {
        return $this->balance >= $amount;
    }
}
