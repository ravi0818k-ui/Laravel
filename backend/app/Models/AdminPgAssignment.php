<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminPgAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'pg_location_id',
        'assigned_by',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function pgLocation()
    {
        return $this->belongsTo(PgLocation::class);
    }

    public function assignedByUser()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
