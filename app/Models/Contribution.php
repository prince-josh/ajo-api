<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contribution extends Model
{
    protected $fillable = [
        'contribution_cycle_id',
        'ajo_group_id',
        'user_id',
        'transaction_id',
        'amount',
        'status',
        'paid_at',
    ];

    protected $casts = [
        'amount'  => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function contributionCycle()
    {
        return $this->belongsTo(ContributionCycle::class);
    }

    public function ajoGroup()
    {
        return $this->belongsTo(AjoGroup::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }
}
