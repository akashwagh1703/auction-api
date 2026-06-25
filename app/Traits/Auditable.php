<?php

namespace App\Traits;

use App\Models\AuditLog;
use App\Jobs\SendAuditLogNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

trait Auditable
{
    public static function bootAuditable()
    {
        static::created(function ($model) {
            $model->logAudit('created');
        });

        static::updated(function ($model) {
            $model->logAudit('updated');
        });

        static::deleted(function ($model) {
            $model->logAudit('deleted');
        });

        static::restored(function ($model) {
            $model->logAudit('restored');
        });
    }

    protected function logAudit($action)
    {
        $auditLog = AuditLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'model_type' => get_class($this),
            'model_id' => $this->id,
            'old_values' => $action === 'updated' ? $this->getOriginal() : null,
            'new_values' => $action === 'deleted' ? null : $this->getAttributes(),
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);

        // Dispatch job for async notification
        SendAuditLogNotification::dispatch(
            $action,
            get_class($this),
            $this->id,
            Auth::id()
        );
    }
}
