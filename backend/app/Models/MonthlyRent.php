<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonthlyRent extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'billing_month',
        'base_rent',
        'discount',
        'additional_charge',
        'total_amount',
        'paid_amount',
        'due_amount',
        'status',
        'due_date',
        'notes',
        'generated_by',
    ];

    protected $casts = [
        'billing_month' => 'date',
        'due_date' => 'date',
        'base_rent' => 'decimal:2',
        'discount' => 'decimal:2',
        'additional_charge' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_amount' => 'decimal:2',
    ];

    // ─── Relationships ──────────────────────────────────────────

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function paymentSubmissions()
    {
        return $this->hasMany(PaymentSubmission::class);
    }

    public function generatedByUser()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    // ─── Helpers ────────────────────────────────────────────────

    /**
     * Recalculate due amount based on verified payments.
     */
    public function recalculateDue(): void
    {
        $verifiedTotal = $this->paymentSubmissions()
            ->where('status', 'verified')
            ->sum('verified_amount');

        $this->paid_amount = $verifiedTotal;
        $this->due_amount = $this->total_amount - $verifiedTotal;

        if ($this->due_amount <= 0) {
            $this->due_amount = 0;
            $this->status = 'paid';
        } elseif ($verifiedTotal > 0) {
            $this->status = 'partially_paid';
        }

        $this->save();
    }

    // ─── Scopes ─────────────────────────────────────────────────

    public function scopeUnpaid($query)
    {
        return $query->where('status', 'unpaid');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', '!=', 'paid')
                     ->where('due_date', '<', now());
    }
}
