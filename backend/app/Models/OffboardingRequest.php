<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OffboardingRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'initiated_by',
        'reason',
        'expected_leaving_date',
        'actual_leaving_date',
        'feedback',
        'outstanding_rent',
        'outstanding_electricity',
        'security_deposit_refund',
        'status',
        'completed_by',
        'completed_at',
        'notes',
    ];

    protected $casts = [
        'expected_leaving_date' => 'date',
        'actual_leaving_date' => 'date',
        'outstanding_rent' => 'decimal:2',
        'outstanding_electricity' => 'decimal:2',
        'security_deposit_refund' => 'decimal:2',
        'completed_at' => 'datetime',
    ];

    // ─── Relationships ──────────────────────────────────────────

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function initiatedByUser()
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function completedByUser()
    {
        return $this->belongsTo(User::class, 'completed_by');
    }
}
