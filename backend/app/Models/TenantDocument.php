<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TenantDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'onboarding_invitation_id',
        'document_type',
        'file_path',
        'original_filename',
        'mime_type',
        'file_size',
        'verification_status',
        'rejection_reason',
        'verified_by',
        'verified_at',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
    ];

    // ─── Relationships ──────────────────────────────────────────

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function onboardingInvitation()
    {
        return $this->belongsTo(OnboardingInvitation::class);
    }

    public function verifiedByUser()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    // ─── Scopes ─────────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('verification_status', 'pending');
    }

    public function scopeVerified($query)
    {
        return $query->where('verification_status', 'verified');
    }

    public function scopeRequiresCorrection($query)
    {
        return $query->where('verification_status', 'correction_required');
    }
}
