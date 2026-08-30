<?php

namespace App\Traits;

use App\Jobs\LogAuditActivity;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

trait AuditLogTrait
{
    protected static function bootAuditLogTrait()
    {
        static::created(function ($model) {
            static::logActivity($model, 'created', null, $model->toArray());
        });

        static::updated(function ($model) {
            static::logActivity(
                $model,
                'updated',
                $model->getOriginal(),
                $model->getChanges()
            );
        });

        static::deleted(function ($model) {
            static::logActivity($model, 'deleted', $model->toArray(), null);
        });
    }

    protected static function logActivity($model, $action, $oldValues, $newValues)
    {
        // request/session context තියෙනවද කියලා safe check (console/queue context නම් null)
        $hasRequest = app()->bound('request') && Request::instance() !== null;

        LogAuditActivity::dispatch(
            userId: Auth::id(),
            companyId: $model->company_id ?? (Auth::check() ? Auth::user()->company_id : null),
            action: $action,
            modelType: get_class($model),
            modelId: $model->id,
            oldValues: $oldValues,
            newValues: $newValues,
            ipAddress: $hasRequest ? Request::ip() : null,
            userAgent: $hasRequest ? Request::userAgent() : null,
            url: $hasRequest ? Request::fullUrl() : null,
            method: $hasRequest ? Request::method() : null,
        );
    }
}