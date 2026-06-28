<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AjoGroup extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'created_by',
        'contribution_amount',
        'frequency',
        'max_members',
        'status',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'contribution_amount' => 'decimal:2',
        'start_date'          => 'date',
        'end_date'            => 'date',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'ajo_group_members')
                    ->withPivot('slot_number', 'status', 'has_collected', 'collected_at', 'is_admin')
                    ->withTimestamps();
    }

    public function groupMembers()
    {
        return $this->hasMany(AjoGroupMember::class);
    }

    public function contributionCycles()
    {
        return $this->hasMany(ContributionCycle::class);
    }

    public function contributions()
    {
        return $this->hasMany(Contribution::class);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    public function currentCycle()
    {
        return $this->contributionCycles()
                    ->where('status', 'active')
                    ->latest()
                    ->first();
    }

    public function getMemberCountAttribute(): int
    {
        return $this->groupMembers()->where('status', 'active')->count();
    }

    public function isFull(): bool
    {
        return $this->member_count >= $this->max_members;
    }

    public function getExpectedCycleTotalAttribute(): float
    {
        return $this->contribution_amount * $this->member_count;
    }

    public static function generateCode(): string
    {
        do {
            $code = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8));
        } while (self::where('code', $code)->exists());

        return $code;
    }
}
