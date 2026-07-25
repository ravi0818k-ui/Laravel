<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ElectricityBillAllocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'electricity_bill_id',
        'tenant_id',
        'amount',
        'status',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    // ─── Relationships ──────────────────────────────────────────

    public function electricityBill()
    {
        return $this->belongsTo(ElectricityBill::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
