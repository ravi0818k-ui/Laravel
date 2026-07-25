<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PgLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'city',
        'state',
        'pincode',
        'latitude',
        'longitude',
        'tenant_id_prefix',
        'tenant_id_counter',
        'contact_mobile',
        'contact_email',
        'description',
        'photos',
        'metadata',
        'starting_rent',
        'is_active',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'starting_rent' => 'decimal:2',
        'photos' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
    ];

    // ─── Relationships ──────────────────────────────────────────

    public function rooms()
    {
        return $this->hasMany(Room::class);
    }

    public function tenants()
    {
        return $this->hasMany(Tenant::class);
    }

    public function concerns()
    {
        return $this->hasMany(Concern::class);
    }

    /**
     * Admins assigned to this PG location.
     */
    public function assignedAdmins()
    {
        return $this->belongsToMany(User::class, 'admin_pg_assignments')
                    ->withPivot('assigned_by')
                    ->withTimestamps();
    }

    // ─── Computed ───────────────────────────────────────────────

    /**
     * Get total available beds count (derived from bed allocations).
     */
    public function getAvailableBedsCountAttribute(): int
    {
        return Bed::whereHas('room', function ($q) {
            $q->where('pg_location_id', $this->id)->where('is_active', true);
        })->where('status', 'available')->where('is_active', true)->count();
    }

    /**
     * Get total beds count.
     */
    public function getTotalBedsCountAttribute(): int
    {
        return Bed::whereHas('room', function ($q) {
            $q->where('pg_location_id', $this->id)->where('is_active', true);
        })->where('is_active', true)->count();
    }

    // ─── Scopes ─────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
