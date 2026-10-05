<?php

namespace App\Models\Concerns;

use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;

/**
 * Records created/updated/deleted events for a model in the audit log.
 *
 * Models may define a protected `$auditExclude` array of attributes that must never be logged.
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function (Model $model): void {
            app(AuditLogger::class)->log(static::auditEvent($model, 'created'), $model, newValues: $model->auditableAttributes($model->getAttributes()));
        });

        static::updated(function (Model $model): void {
            $changes = $model->auditableAttributes($model->getChanges());
            unset($changes['updated_at']);

            if ($changes === []) {
                return;
            }

            $original = array_intersect_key($model->getOriginal(), $changes);

            app(AuditLogger::class)->log(static::auditEvent($model, 'updated'), $model, $model->auditableAttributes($original), $changes);
        });

        static::deleted(function (Model $model): void {
            app(AuditLogger::class)->log(static::auditEvent($model, 'deleted'), $model);
        });
    }

    protected static function auditEvent(Model $model, string $action): string
    {
        return str(class_basename($model))->snake()->append('.', $action)->toString();
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function auditableAttributes(array $attributes): array
    {
        $exclude = array_merge(['password', 'remember_token'], $this->auditExclude ?? []);

        return collect($attributes)
            ->except($exclude)
            ->map(fn (mixed $value): mixed => $value instanceof \BackedEnum ? $value->value : $value)
            ->map(fn (mixed $value): mixed => $value instanceof \DateTimeInterface ? $value->format('Y-m-d H:i:s') : $value)
            ->all();
    }
}
