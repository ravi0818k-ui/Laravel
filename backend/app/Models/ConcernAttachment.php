<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConcernAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'concern_id',
        'file_path',
        'original_filename',
        'mime_type',
        'file_size',
    ];

    public function concern()
    {
        return $this->belongsTo(Concern::class);
    }
}
