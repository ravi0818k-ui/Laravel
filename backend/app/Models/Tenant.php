<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'tenant_id',
        'pg_location_id',
        'date_of_birth',
        'blood_group',
        'company_or_college',
        'company_college_address',
        'parent_mobile',
        'reference_mobile_1',
        'reference_mobile_2',
        'emergency_contact_name',
        'emergency_contact_mobile',
        'referral_code',
        'referred_by_code',
        'joining_date',
        'current_rent',
        'security_deposit',
        'status',
        'offboarded_at',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'joining_date' => 'date',
        'current_rent' => 'decimal:2',
        'security_deposit' => 'decimal:2',
        'offboarded_at' => 'datetime',
    ];

    // ─── Relationships ──────────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function pgLocation()
    {
        return $this->belongsTo(PgLocation::class);
    }

    public function bedAllocations()
    {
        return $this->hasMany(TenantBedAllocation::class);
    }

    public function currentBedAllocation()
    {
        return $this->hasOne(TenantBedAllocation::class)->where('is_current', true);
    }

    public function rentHistory()
    {
        return $this->hasMany(TenantRentHistory::class);
    }

    public function documents()
    {
        return $this->hasMany(TenantDocument::class);
    }

    public function monthlyRents()
    {
        return $this->hasMany(MonthlyRent::class);
    }

    public function paymentSubmissions()
    {
        return $this->hasMany(PaymentSubmission::class);
    }

    public function electricityBillAllocations()
    {
        return $this->hasMany(ElectricityBillAllocation::class);
    }

    public function concerns()
    {
        return $this->hasMany(Concern::class);
    }

    public function offboardingRequests()
    {
        return $this->hasMany(OffboardingRequest::class);
    }

    /**
     * People this tenant has referred.
     */
    public function referralsMade()
    {
        return $this->hasMany(Referral::class, 'referrer_tenant_id');
    }

    /**
     * The referral that brought this tenant in.
     */
    public function referredBy()
    {
        return $this->hasOne(Referral::class, 'referred_tenant_id');
    }

    // ─── Computed ───────────────────────────────────────────────

    public function getCurrentBedAttribute()
    {
        return $this->currentBedAllocation?->bed;
    }

    public function getCurrentRoomAttribute()
    {
        return $this->currentBed?->room;
    }

    /**
     * Roommates: other tenants in the same room.
     */
    public function getRoommatesAttribute()
    {
        $currentBed = $this->currentBed;
        if (!$currentBed) return collect();

        return self::with('user')
            ->whereHas('currentBedAllocation', function ($q) use ($currentBed) {
                $q->whereHas('bed', function ($bq) use ($currentBed) {
                    $bq->where('room_id', $currentBed->room_id);
                });
            })->where('id', '!=', $this->id)->get();
    }

    // ─── Scopes ─────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeOffboarded($query)
    {
        return $query->where('status', 'offboarded');
    }
}
