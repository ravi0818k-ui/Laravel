<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ElectricityBill extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_id',
        'billing_month',
        'total_units',
        'rate_per_unit',
        'total_amount',
        'active_tenants_count',
        'per_tenant_amount',
        'entered_by',
        'notes',
        'previous_meter_image',
        'current_meter_image',
        'previous_reading',
        'current_reading',
    ];

    protected $casts = [
        'billing_month' => 'date',
        'total_units' => 'decimal:2',
        'rate_per_unit' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'per_tenant_amount' => 'decimal:2',
        'previous_reading' => 'decimal:2',
        'current_reading' => 'decimal:2',
    ];

    // ─── Relationships ──────────────────────────────────────────

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function allocations()
    {
        return $this->hasMany(ElectricityBillAllocation::class);
    }

    public function enteredByUser()
    {
        return $this->belongsTo(User::class, 'entered_by');
    }
}
