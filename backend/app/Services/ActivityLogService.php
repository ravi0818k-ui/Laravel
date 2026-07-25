<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ActivityLogService
{
    /**
     * Log an activity/audit entry.
     */
    public function log(
        string $action,
        ?Model $model = null,
        ?array $before = null,
        ?array $after = null,
        ?string $description = null,
        ?int $impersonatedBy = null
    ): ActivityLog {
        return ActivityLog::create([
            'user_id' => Auth::id(),
            'impersonated_by' => $impersonatedBy,
            'action' => $action,
            'model_type' => $model ? get_class($model) : null,
            'model_id' => $model?->id,
            'before' => $before,
            'after' => $after,
            'description' => $description,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'created_at' => now(),
        ]);
    }
}
