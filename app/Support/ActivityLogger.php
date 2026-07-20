<?php

namespace App\Support;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class ActivityLogger
{
    /** Cached availability of the activity_logs table (avoids a Schema query per event). */
    private static ?bool $tableExists = null;

    /**
     * Record an activity entry. Never throws — a logging failure must not break the
     * business action that triggered it.
     */
    public static function log(
        string $event,
        string $description,
        ?Model $subject = null,
        array $properties = [],
        ?User $causer = null
    ): void {
        try {
            if (! self::tableExists()) {
                return;
            }

            $causer = $causer ?? self::currentUser();

            // Model-change events require a human actor; system/console writes are ignored
            // so migrations, seeders and cron jobs don't flood the log. Auth events always
            // pass their user explicitly.
            if ($causer === null && ! in_array($event, [ActivityLog::EVENT_LOGIN, ActivityLog::EVENT_LOGOUT], true)) {
                return;
            }

            $tenantId = self::resolveTenantId($subject, $causer);

            $log = new ActivityLog([
                'user_id' => $causer?->id,
                'causer_name' => $causer?->name,
                'causer_role' => $causer?->role,
                'event' => $event,
                'description' => $description,
                'subject_type' => $subject ? $subject->getMorphClass() : null,
                'subject_id' => $subject?->getKey(),
                'properties' => $properties ?: null,
                'ip_address' => self::currentIp(),
            ]);

            // Set tenant explicitly so cross-tenant/superadmin actions attribute correctly;
            // BelongsToTenant only auto-fills when this is left null.
            $log->tenant_id = $tenantId;

            $log->save();
        } catch (\Throwable $e) {
            // Swallow — logging is best-effort.
        }
    }

    private static function tableExists(): bool
    {
        if (self::$tableExists === null) {
            try {
                self::$tableExists = Schema::hasTable('activity_logs');
            } catch (\Throwable $e) {
                self::$tableExists = false;
            }
        }

        return self::$tableExists;
    }

    private static function currentUser(): ?User
    {
        try {
            $user = Auth::user();
            if ($user instanceof User) {
                return $user;
            }

            $requestUser = request()?->user();

            return $requestUser instanceof User ? $requestUser : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private static function resolveTenantId(?Model $subject, ?User $causer): ?int
    {
        $subjectTenant = $subject?->getAttribute('tenant_id');
        if ($subjectTenant !== null) {
            return (int) $subjectTenant;
        }

        if ($causer?->tenant_id !== null) {
            return (int) $causer->tenant_id;
        }

        return TenantContext::id();
    }

    private static function currentIp(): ?string
    {
        try {
            return request()?->ip();
        } catch (\Throwable $e) {
            return null;
        }
    }
}
