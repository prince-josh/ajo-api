<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'password',
        'account_number',
        'role',
        'avatar',
        'address',
        'state',
        'is_active',
        'is_verified',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
        'is_active'         => 'boolean',
        'is_verified'       => 'boolean',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function ajoGroups()
    {
        return $this->belongsToMany(AjoGroup::class, 'ajo_group_members')
                    ->withPivot('slot_number', 'status', 'has_collected', 'collected_at', 'is_admin')
                    ->withTimestamps();
    }

    public function createdGroups()
    {
        return $this->hasMany(AjoGroup::class, 'created_by');
    }

    public function contributions()
    {
        return $this->hasMany(Contribution::class);
    }

    public function payoutCycles()
    {
        return $this->hasMany(ContributionCycle::class, 'recipient_id');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'super_admin']);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }
}
