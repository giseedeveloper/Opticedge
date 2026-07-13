<?php

namespace App\Services;

use App\Models\ContractTerminationRequest;
use App\Models\GuestVendorInvitation;
use App\Models\User;
use App\Models\UserRating;
use App\Models\UserVendorTenure;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

class WorkerReputationService
{
    private const FIELD_ROLES = ['agent', 'teamleader', 'regional_manager'];

    public function openTenureFromInvitation(GuestVendorInvitation $invitation, User $user): UserVendorTenure
    {
        $existing = UserVendorTenure::query()
            ->where('user_id', $user->id)
            ->where('tenant_id', $invitation->tenant_id)
            ->whereNull('ended_at')
            ->first();

        if ($existing) {
            $existing->update([
                'role' => $invitation->proposed_role,
                'invitation_id' => $invitation->id,
                'source' => UserVendorTenure::SOURCE_INVITATION,
            ]);

            return $existing->fresh();
        }

        return UserVendorTenure::create([
            'user_id' => $user->id,
            'tenant_id' => $invitation->tenant_id,
            'role' => $invitation->proposed_role,
            'started_at' => $invitation->responded_at ?? now(),
            'ended_at' => null,
            'source' => UserVendorTenure::SOURCE_INVITATION,
            'invitation_id' => $invitation->id,
        ]);
    }

    public function openTenureForUser(User $user, string $source = UserVendorTenure::SOURCE_DIRECT_ASSIGN): ?UserVendorTenure
    {
        if (! in_array($user->role, self::FIELD_ROLES, true) || $user->tenant_id === null) {
            return null;
        }

        $existing = UserVendorTenure::query()
            ->where('user_id', $user->id)
            ->where('tenant_id', $user->tenant_id)
            ->whereNull('ended_at')
            ->first();

        if ($existing) {
            return $existing;
        }

        return UserVendorTenure::create([
            'user_id' => $user->id,
            'tenant_id' => $user->tenant_id,
            'role' => $user->role,
            'started_at' => $user->updated_at ?? now(),
            'ended_at' => null,
            'source' => $source,
        ]);
    }

    public function closeTenure(
        User $user,
        int $tenantId,
        ?ContractTerminationRequest $termination = null,
    ): ?UserVendorTenure {
        $tenure = UserVendorTenure::query()
            ->where('user_id', $user->id)
            ->where('tenant_id', $tenantId)
            ->whereNull('ended_at')
            ->orderByDesc('started_at')
            ->first();

        if (! $tenure) {
            $role = $termination?->role_at_request ?? 'agent';
            $startedAt = $termination?->created_at ?? now()->subDay();

            $tenure = UserVendorTenure::create([
                'user_id' => $user->id,
                'tenant_id' => $tenantId,
                'role' => $role,
                'started_at' => $startedAt,
                'ended_at' => now(),
                'source' => UserVendorTenure::SOURCE_BACKFILL,
                'termination_request_id' => $termination?->id,
            ]);

            return $tenure;
        }

        $tenure->update([
            'ended_at' => now(),
            'termination_request_id' => $termination?->id,
        ]);

        return $tenure->fresh();
    }

    /**
     * @return UserRating
     */
    public function upsertRating(
        User $ratedUser,
        User $rater,
        int $tenantId,
        int $score,
        ?string $comment = null,
        string $source = UserRating::SOURCE_MANUAL,
        ?UserVendorTenure $tenure = null,
    ): UserRating {
        if ($score < 1 || $score > 5) {
            throw new InvalidArgumentException('Rating score must be between 1 and 5.');
        }

        if (! in_array($rater->role, ['admin', 'subadmin'], true)) {
            abort(403);
        }

        if ((int) $rater->tenant_id !== $tenantId) {
            abort(403);
        }

        if ($tenure === null) {
            $tenure = UserVendorTenure::query()
                ->where('user_id', $ratedUser->id)
                ->where('tenant_id', $tenantId)
                ->orderByDesc('started_at')
                ->first();
        }

        $rating = UserRating::query()
            ->where('rated_user_id', $ratedUser->id)
            ->where('tenant_id', $tenantId)
            ->first();

        $payload = [
            'rater_user_id' => $rater->id,
            'score' => $score,
            'comment' => $comment !== null && trim($comment) !== '' ? trim($comment) : null,
            'tenure_id' => $tenure?->id,
            'source' => $source,
        ];

        if ($rating) {
            $rating->update($payload);

            return $rating->fresh(['tenant', 'rater']);
        }

        return UserRating::create([
            'rated_user_id' => $ratedUser->id,
            'tenant_id' => $tenantId,
            ...$payload,
        ])->fresh(['tenant', 'rater']);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function workHistory(User $user): array
    {
        if (! Schema::hasTable('user_vendor_tenures')) {
            return [];
        }

        return UserVendorTenure::query()
            ->where('user_id', $user->id)
            ->with(['tenant:id,name,brand_name'])
            ->orderByDesc('started_at')
            ->get()
            ->map(fn (UserVendorTenure $t) => $t->toHistoryArray())
            ->values()
            ->all();
    }

    /**
     * @return array{average: float|null, count: int, ratings: list<array<string, mixed>>}
     */
    public function ratingSummary(User $user, int $recentLimit = 20): array
    {
        if (! Schema::hasTable('user_ratings')) {
            return ['average' => null, 'count' => 0, 'ratings' => []];
        }

        $ratings = UserRating::query()
            ->where('rated_user_id', $user->id)
            ->with(['tenant:id,name,brand_name', 'rater:id,name'])
            ->orderByDesc('updated_at')
            ->get();

        $count = $ratings->count();
        $average = $count > 0 ? round((float) $ratings->avg('score'), 2) : null;

        return [
            'average' => $average,
            'count' => $count,
            'ratings' => $ratings->take($recentLimit)->map(fn (UserRating $r) => $r->toPublicArray())->values()->all(),
        ];
    }

    /**
     * @return array{avg_rating: float|null, ratings_count: int}
     */
    public function ratingTotalsForUserIds(array $userIds): array
    {
        if ($userIds === [] || ! Schema::hasTable('user_ratings')) {
            return [];
        }

        $rows = UserRating::query()
            ->selectRaw('rated_user_id, AVG(score) as avg_rating, COUNT(*) as ratings_count')
            ->whereIn('rated_user_id', $userIds)
            ->groupBy('rated_user_id')
            ->get()
            ->keyBy('rated_user_id');

        $out = [];
        foreach ($userIds as $id) {
            $row = $rows->get($id);
            $out[$id] = [
                'avg_rating' => $row ? round((float) $row->avg_rating, 2) : null,
                'ratings_count' => $row ? (int) $row->ratings_count : 0,
            ];
        }

        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    public function profileReputationPayload(User $user): array
    {
        $summary = $this->ratingSummary($user);

        return [
            'experience_bio' => Schema::hasColumn('users', 'experience_bio') ? $user->experience_bio : null,
            'work_history' => Schema::hasTable('user_vendor_tenures') ? $this->workHistory($user) : [],
            'rating_summary' => [
                'average' => $summary['average'],
                'count' => $summary['count'],
            ],
            'ratings' => $summary['ratings'],
        ];
    }

    public function backfillTenures(): int
    {
        if (! Schema::hasTable('user_vendor_tenures')) {
            return 0;
        }

        $created = 0;

        $accepted = GuestVendorInvitation::query()
            ->where('status', GuestVendorInvitation::STATUS_ACCEPTED)
            ->orderBy('responded_at')
            ->get();

        foreach ($accepted as $invitation) {
            $exists = UserVendorTenure::query()
                ->where('invitation_id', $invitation->id)
                ->exists();

            if ($exists) {
                continue;
            }

            $termination = ContractTerminationRequest::query()
                ->where('user_id', $invitation->guest_user_id)
                ->where('tenant_id', $invitation->tenant_id)
                ->where('status', ContractTerminationRequest::STATUS_APPROVED)
                ->where('decided_at', '>=', $invitation->responded_at ?? $invitation->created_at)
                ->orderBy('decided_at')
                ->first();

            UserVendorTenure::create([
                'user_id' => $invitation->guest_user_id,
                'tenant_id' => $invitation->tenant_id,
                'role' => $invitation->proposed_role,
                'started_at' => $invitation->responded_at ?? $invitation->created_at ?? now(),
                'ended_at' => $termination?->decided_at,
                'source' => UserVendorTenure::SOURCE_BACKFILL,
                'invitation_id' => $invitation->id,
                'termination_request_id' => $termination?->id,
            ]);
            $created++;
        }

        $currentWorkers = User::withoutGlobalScopes()
            ->whereIn('role', self::FIELD_ROLES)
            ->whereNotNull('tenant_id')
            ->get();

        foreach ($currentWorkers as $worker) {
            $open = UserVendorTenure::query()
                ->where('user_id', $worker->id)
                ->where('tenant_id', $worker->tenant_id)
                ->whereNull('ended_at')
                ->exists();

            if ($open) {
                continue;
            }

            UserVendorTenure::create([
                'user_id' => $worker->id,
                'tenant_id' => $worker->tenant_id,
                'role' => $worker->role,
                'started_at' => $worker->created_at ?? now(),
                'ended_at' => null,
                'source' => UserVendorTenure::SOURCE_BACKFILL,
            ]);
            $created++;
        }

        $terminations = ContractTerminationRequest::query()
            ->where('status', ContractTerminationRequest::STATUS_APPROVED)
            ->orderBy('decided_at')
            ->get();

        foreach ($terminations as $termination) {
            $linked = UserVendorTenure::query()
                ->where('termination_request_id', $termination->id)
                ->exists();

            if ($linked) {
                continue;
            }

            $overlapping = UserVendorTenure::query()
                ->where('user_id', $termination->user_id)
                ->where('tenant_id', $termination->tenant_id)
                ->where(function ($q) use ($termination) {
                    $q->whereNull('ended_at')
                        ->orWhere('ended_at', '>=', $termination->decided_at ?? $termination->updated_at);
                })
                ->exists();

            if ($overlapping) {
                continue;
            }

            UserVendorTenure::create([
                'user_id' => $termination->user_id,
                'tenant_id' => $termination->tenant_id,
                'role' => $termination->role_at_request,
                'started_at' => $termination->created_at ?? now()->subDay(),
                'ended_at' => $termination->decided_at ?? $termination->updated_at,
                'source' => UserVendorTenure::SOURCE_BACKFILL,
                'termination_request_id' => $termination->id,
            ]);
            $created++;
        }

        return $created;
    }
}
