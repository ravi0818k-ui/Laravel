<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Referral extends Model
{
    use HasFactory;

    protected $fillable = [
        'referrer_tenant_id',
        'referred_tenant_id',
        'onboarding_invitation_id',
        'referral_code_used',
        'status',
        'reward_type',
        'reward_amount',
        'reward_applied',
    ];

    protected $casts = [
        'reward_amount' => 'decimal:2',
        'reward_applied' => 'boolean',
    ];

    // ─── Relationships ──────────────────────────────────────────

    public function referrer()
    {
        return $this->belongsTo(Tenant::class, 'referrer_tenant_id');
    }

    public function referredTenant()
    {
        return $this->belongsTo(Tenant::class, 'referred_tenant_id');
    }

    public function onboardingInvitation()
    {
        return $this->belongsTo(OnboardingInvitation::class);
    }
}
