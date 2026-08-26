<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

trait AuditLogTrait
{
    protected static function bootAuditLogTrait()
    {
        // Log created
        static::created(function ($model) {
            static::logActivity($model, 'created', null, $model->toArray());
        });

        // Log updated
        static::updated(function ($model) {
            static::logActivity(
                $model,
                'updated',
                $model->getOriginal(),
                $model->getChanges()
            );
        });

        // Log deleted
        static::deleted(function ($model) {
            static::logActivity($model, 'deleted', $model->toArray(), null);
        });
    }

    protected static function logActivity($model, $action, $oldValues, $newValues)
    {
        AuditLog::create([
            'user_id' => Auth::id(),
            'company_id' => $model->company_id ?? Auth::user()->company_id ?? null,
            'action' => $action,
            'model_type' => get_class($model),
            'model_id' => $model->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'url' => Request::fullUrl(),
            'method' => Request::method(),
        ]);
    }
}