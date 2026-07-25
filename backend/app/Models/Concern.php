<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Concern extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'pg_location_id',
        'category',
        'subject',
        'description',
        'status',
        'priority',
        'resolved_by',
        'resolved_at',
        'admin_notes',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function pgLocation()
    {
        return $this->belongsTo(PgLocation::class);
    }

    public function resolvedByUser()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function attachments()
    {
        return $this->hasMany(ConcernAttachment::class);
    }
}
