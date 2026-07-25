<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'monthly_rent_id',
        'tenant_id',
        'claimed_amount',
        'verified_amount',
        'payment_method',
        'transaction_reference',
        'screenshot_path',
        'status',
        'rejection_reason',
        'verified_by',
        'verified_at',
        'payment_date',
        'notes',
    ];

    protected $casts = [
        'claimed_amount' => 'decimal:2',
        'verified_amount' => 'decimal:2',
        'verified_at' => 'datetime',
        'payment_date' => 'date',
    ];

    // ─── Relationships ──────────────────────────────────────────

    public function monthlyRent()
    {
        return $this->belongsTo(MonthlyRent::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function verifiedByUser()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    // ─── Scopes ─────────────────────────────────────────────────

    public function scopePendingVerification($query)
    {
        return $query->whereIn('status', ['submitted', 'verification_pending']);
    }

    public function scopeVerified($query)
    {
        return $query->where('status', 'verified');
    }
}
