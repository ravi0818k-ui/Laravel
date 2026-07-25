<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TenantBedAllocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'bed_id',
        'allocated_at',
        'vacated_at',
        'is_current',
        'notes',
    ];

    protected $casts = [
        'allocated_at' => 'date',
        'vacated_at' => 'date',
        'is_current' => 'boolean',
    ];

    // ─── Relationships ──────────────────────────────────────────

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function bed()
    {
        return $this->belongsTo(Bed::class);
    }

    // ─── Scopes ─────────────────────────────────────────────────

    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }
}
