<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TenantRentHistory extends Model
{
    use HasFactory;

    protected $table = 'tenant_rent_history';

    protected $fillable = [
        'tenant_id',
        'previous_rent',
        'new_rent',
        'effective_date',
        'reason',
        'changed_by',
    ];

    protected $casts = [
        'previous_rent' => 'decimal:2',
        'new_rent' => 'decimal:2',
        'effective_date' => 'date',
    ];

    // ─── Relationships ──────────────────────────────────────────

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function changedByUser()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
