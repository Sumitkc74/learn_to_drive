<?php

namespace App\Observers;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class AdminAuditObserver
{
    private const SENSITIVE = [
        'password', 'remember_token', 'phone_verification_code', 'phone_verification_expires_at',
    ];

    public function created(Model $model): void
    {
        $this->record($model, 'created', [], $model->getAttributes());
    }

    public function updated(Model $model): void
    {
        $changes = $model->getChanges();
        unset($changes['updated_at']);

        if ($changes !== []) {
            $old = array_intersect_key($model->getOriginal(), $changes);
            $this->record($model, 'updated', $old, $changes);
        }
    }

    public function deleted(Model $model): void
    {
        $event = method_exists($model, 'isForceDeleting') && $model->isForceDeleting()
            ? 'permanently_deleted'
            : 'deleted';
        $this->record($model, $event, $model->getAttributes(), []);
    }

    public function restored(Model $model): void
    {
        $this->record($model, 'restored', [], $model->getAttributes());
    }

    private function record(Model $model, string $event, array $old, array $new): void
    {
        $request = app()->bound('request') ? request() : null;

        AuditLog::create([
            'actor_id' => auth()->id(),
            'event' => $event,
            'subject_type' => class_basename($model),
            'subject_id' => $model->getKey(),
            'subject_label' => $model->name ?? $model->question ?? $model->email ?? class_basename($model).' #'.$model->getKey(),
            'old_values' => $this->safe($old),
            'new_values' => $this->safe($new),
            'ip_address' => $request?->ip(),
            'user_agent' => mb_substr((string) $request?->userAgent(), 0, 500),
        ]);
    }

    private function safe(array $values): array
    {
        return array_diff_key($values, array_flip(self::SENSITIVE));
    }
}
