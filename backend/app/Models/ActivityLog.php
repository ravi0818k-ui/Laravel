<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'impersonated_by',
        'action',
        'model_type',
        'model_id',
        'before',
        'after',
        'description',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'before' => 'array',
        'after' => 'array',
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function impersonator()
    {
        return $this->belongsTo(User::class, 'impersonated_by');
    }

    public function subject()
    {
        return $this->morphTo('model');
    }
}
