<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bed extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_id',
        'bed_number',
        'monthly_rent',
        'status',
        'description',
        'is_active',
    ];

    protected $attributes = [
        'status' => 'available',
        'is_active' => true,
    ];

    protected $casts = [
        'monthly_rent' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // ─── Relationships ──────────────────────────────────────────

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function allocations()
    {
        return $this->hasMany(TenantBedAllocation::class);
    }

    public function currentAllocation()
    {
        return $this->hasOne(TenantBedAllocation::class)->where('is_current', true);
    }

    public function currentTenant()
    {
        return $this->hasOneThrough(
            Tenant::class,
            TenantBedAllocation::class,
            'bed_id',      // FK on tenant_bed_allocations
            'id',          // FK on tenants
            'id',          // Local key on beds
            'tenant_id'    // Local key on tenant_bed_allocations
        )->where('tenant_bed_allocations.is_current', true);
    }

    // ─── Scopes ─────────────────────────────────────────────────

    public function scopeAvailable($query)
    {
        return $query->where('status', 'available')->where('is_active', true);
    }

    public function scopeOccupied($query)
    {
        return $query->where('status', 'occupied');
    }
}
