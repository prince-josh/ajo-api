<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AjoGroupMember extends Model
{
    protected $fillable = [
        'ajo_group_id',
        'user_id',
        'slot_number',
        'status',
        'has_collected',
        'collected_at',
        'is_admin',
    ];

    protected $casts = [
        'has_collected' => 'boolean',
        'is_admin'      => 'boolean',
        'collected_at'  => 'datetime',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function ajoGroup()
    {
        return $this->belongsTo(AjoGroup::class);
    }
}
