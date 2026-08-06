<?php

namespace App\Traits;

use App\Models\AuditLog;

/**
 * Trait барои сабти автоматии тағйиротҳо дар audit log
 *
 * Истифода: use Auditable; дар модел
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        // Баъди сохтан
        static::created(function ($model) {
            if (!config('donishor.audit.enabled', true)) return;

            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'create',
                'model_type' => get_class($model),
                'model_id' => $model->id,
                'description' => 'Сохтани ' . class_basename($model) . ' #' . $model->id,
                'new_values' => $model->getAttributes(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'url' => request()->fullUrl(),
                'method' => request()->method(),
            ]);
        });

        // Баъди навсозӣ
        static::updated(function ($model) {
            if (!config('donishor.audit.enabled', true)) return;

            $changes = $model->getChanges();
            $original = array_intersect_key($model->getOriginal(), $changes);

            // Пинҳон кардани парол
            unset($changes['password'], $original['password']);
            unset($changes['remember_token'], $original['remember_token']);

            if (empty($changes)) return;

            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'update',
                'model_type' => get_class($model),
                'model_id' => $model->id,
                'description' => 'Навсозии ' . class_basename($model) . ' #' . $model->id,
                'old_values' => $original,
                'new_values' => $changes,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'url' => request()->fullUrl(),
                'method' => request()->method(),
            ]);
        });

        // Баъди нест кардан
        static::deleted(function ($model) {
            if (!config('donishor.audit.enabled', true)) return;

            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'delete',
                'model_type' => get_class($model),
                'model_id' => $model->id,
                'description' => 'Нест кардани ' . class_basename($model) . ' #' . $model->id,
                'old_values' => $model->getAttributes(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'url' => request()->fullUrl(),
                'method' => request()->method(),
            ]);
        });
    }
}
