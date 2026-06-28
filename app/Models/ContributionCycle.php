<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContributionCycle extends Model
{
    protected $fillable = [
        'ajo_group_id',
        'recipient_id',
        'cycle_number',
        'expected_total',
        'amount_collected',
        'status',
        'due_date',
        'paid_out_at',
    ];

    protected $casts = [
        'expected_total'   => 'decimal:2',
        'amount_collected' => 'decimal:2',
        'due_date'         => 'date',
        'paid_out_at'      => 'datetime',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function ajoGroup()
    {
        return $this->belongsTo(AjoGroup::class);
    }

    public function recipient()
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function contributions()
    {
        return $this->hasMany(Contribution::class);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    public function isFullyFunded(): bool
    {
        return $this->amount_collected >= $this->expected_total;
    }

    public function getPaidContributionsCountAttribute(): int
    {
        return $this->contributions()->where('status', 'paid')->count();
    }

    public function getDefaultedContributionsCountAttribute(): int
    {
        return $this->contributions()->where('status', 'defaulted')->count();
    }
}
