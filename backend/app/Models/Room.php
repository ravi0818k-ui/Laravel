<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'pg_location_id',
        'room_number',
        'floor',
        'room_type',
        'total_beds',
        'has_attached_bathroom',
        'has_ac',
        'has_balcony',
        'description',
        'is_active',
    ];

    protected $attributes = [
        'is_active' => true,
        'has_attached_bathroom' => false,
        'has_ac' => false,
        'has_balcony' => false,
    ];

    protected $casts = [
        'has_attached_bathroom' => 'boolean',
        'has_ac' => 'boolean',
        'has_balcony' => 'boolean',
        'is_active' => 'boolean',
    ];

    // ─── Relationships ──────────────────────────────────────────

    public function pgLocation()
    {
        return $this->belongsTo(PgLocation::class);
    }

    public function beds()
    {
        return $this->hasMany(Bed::class);
    }

    public function electricityBills()
    {
        return $this->hasMany(ElectricityBill::class);
    }

    // ─── Computed ───────────────────────────────────────────────

    public function getAvailableBedsCountAttribute(): int
    {
        return $this->beds()->where('status', 'available')->where('is_active', true)->count();
    }

    public function getOccupiedBedsCountAttribute(): int
    {
        return $this->beds()->where('status', 'occupied')->count();
    }

    /**
     * Get currently active tenants in this room.
     */
    public function getCurrentTenantsAttribute()
    {
        return Tenant::whereHas('currentBedAllocation', function ($q) {
            $q->whereHas('bed', function ($bq) {
                $bq->where('room_id', $this->id);
            });
        })->get();
    }

    // ─── Scopes ─────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
