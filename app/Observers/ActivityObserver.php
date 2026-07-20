<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Support\ActivityLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Records create / update / delete activity for the curated set of business models
 * registered in AppServiceProvider. Attached only to those models, so it never fires
 * for framework/internal tables or for ActivityLog itself.
 */
class ActivityObserver
{
    /** Attributes never worth recording in an update diff. */
    private const NOISE_ATTRIBUTES = [
        'updated_at',
        'created_at',
        'password',
        'remember_token',
        'api_token',
    ];

    public function created(Model $model): void
    {
        ActivityLogger::log(
            ActivityLog::EVENT_CREATED,
            $this->actor().' created '.$this->label($model),
            $model
        );
    }

    public function updated(Model $model): void
    {
        $changes = $this->changes($model);

        if (empty($changes)) {
            return;
        }

        $description = $this->actor().' updated '.$this->label($model);

        // Surface status transitions (approvals / rejections / state changes) in the summary.
        if (array_key_exists('status', $changes)) {
            $description .= ' — status: '.($changes['status']['from'] ?? '—').' → '.($changes['status']['to'] ?? '—');
        }

        ActivityLogger::log(
            ActivityLog::EVENT_UPDATED,
            $description,
            $model,
            ['changes' => $changes]
        );
    }

    public function deleted(Model $model): void
    {
        ActivityLogger::log(
            ActivityLog::EVENT_DELETED,
            $this->actor().' deleted '.$this->label($model),
            $model
        );
    }

    /** Human-readable actor prefix falls back to "Someone" when no user resolves. */
    private function actor(): string
    {
        $user = \Illuminate\Support\Facades\Auth::user();

        if (! $user) {
            return 'Someone';
        }

        $role = $user->role ? ' ('.str_replace('_', ' ', $user->role).')' : '';

        return $user->name.$role;
    }

    /** e.g. "Agent Sale #42 (INV-100)". */
    private function label(Model $model): string
    {
        $type = Str::headline(class_basename($model));
        $name = $this->subjectName($model);
        $key = $model->getKey();

        $label = $type.' #'.$key;

        if ($name !== null && $name !== (string) $key) {
            $label .= ' ('.Str::limit($name, 60).')';
        }

        return $label;
    }

    private function subjectName(Model $model): ?string
    {
        foreach (['name', 'title', 'activity', 'invoice_number', 'reference', 'imei', 'email'] as $attr) {
            $value = $model->getAttribute($attr);
            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    /**
     * Old → new diff of changed columns, excluding noise/sensitive attributes.
     *
     * @return array<string, array{from: mixed, to: mixed}>
     */
    private function changes(Model $model): array
    {
        $changes = [];
        $original = $model->getOriginal();

        foreach ($model->getChanges() as $key => $new) {
            if (in_array($key, self::NOISE_ATTRIBUTES, true)) {
                continue;
            }

            $changes[$key] = [
                'from' => $original[$key] ?? null,
                'to' => $new,
            ];
        }

        return $changes;
    }
}
