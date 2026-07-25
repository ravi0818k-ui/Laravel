<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'mobile',
        'password',
        'role',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    // ─── Role Helpers ───────────────────────────────────────────

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isTenant(): bool
    {
        return $this->role === 'tenant';
    }

    // ─── Relationships ──────────────────────────────────────────

    public function tenant()
    {
        return $this->hasOne(Tenant::class);
    }

    /**
     * PG locations assigned to this admin.
     */
    public function assignedPgLocations()
    {
        return $this->belongsToMany(PgLocation::class, 'admin_pg_assignments')
                    ->withPivot('assigned_by')
                    ->withTimestamps();
    }

    /**
     * Admin PG assignments (direct relation).
     */
    public function adminPgAssignments()
    {
        return $this->hasMany(AdminPgAssignment::class);
    }

    /**
     * Activity logs performed by this user.
     */
    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    // ─── Scopes ─────────────────────────────────────────────────

    public function scopeAdmins($query)
    {
        return $query->where('role', 'admin');
    }

    public function scopeTenants($query)
    {
        return $query->where('role', 'tenant');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
