<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OnboardingInvitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'token',
        'pg_location_id',
        'created_by',
        'status',
        'link_type',
        'expires_at',
        'submitted_at',
        'candidate_name',
        'candidate_mobile',
        'candidate_dob',
        'candidate_blood_group',
        'candidate_company_college',
        'candidate_company_college_address',
        'candidate_parent_mobile',
        'candidate_reference_mobile_1',
        'candidate_reference_mobile_2',
        'preferred_pg_location_id',
        'referral_code_used',
        'admin_notes',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'submitted_at' => 'datetime',
        'candidate_dob' => 'date',
    ];

    // ─── Relationships ──────────────────────────────────────────

    public function pgLocation()
    {
        return $this->belongsTo(PgLocation::class);
    }

    public function preferredPgLocation()
    {
        return $this->belongsTo(PgLocation::class, 'preferred_pg_location_id');
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function documents()
    {
        return $this->hasMany(TenantDocument::class);
    }

    // ─── Helpers ────────────────────────────────────────────────

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isUsable(): bool
    {
        return $this->status === 'pending' && !$this->isExpired();
    }
}
