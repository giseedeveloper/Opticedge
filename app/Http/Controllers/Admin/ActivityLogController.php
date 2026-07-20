<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    /**
     * System log: user activity (login/logout, create/update/delete) within the vendor.
     */
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'q' => 'nullable|string|max:100',
            'role' => 'nullable|string|max:50',
            'event' => 'nullable|string|max:30',
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
        ]);

        $query = ActivityLog::query()->with('user:id,name,role');

        if (! empty($validated['q'])) {
            $term = '%'.$validated['q'].'%';
            $query->where(function ($q) use ($term) {
                $q->where('causer_name', 'like', $term)
                    ->orWhere('description', 'like', $term);
            });
        }

        if (! empty($validated['role'])) {
            $query->where('causer_role', $validated['role']);
        }

        if (! empty($validated['event'])) {
            $query->where('event', $validated['event']);
        }

        if (! empty($validated['from'])) {
            $query->whereDate('created_at', '>=', $validated['from']);
        }

        if (! empty($validated['to'])) {
            $query->whereDate('created_at', '<=', $validated['to']);
        }

        $logs = $query->latest('id')->paginate(50)->withQueryString();

        // Distinct roles/events present in this tenant's log for the filter dropdowns.
        $roles = ActivityLog::query()
            ->whereNotNull('causer_role')
            ->distinct()
            ->orderBy('causer_role')
            ->pluck('causer_role');

        $events = ActivityLog::query()
            ->distinct()
            ->orderBy('event')
            ->pluck('event');

        return view('admin.activity-logs.index', [
            'logs' => $logs,
            'roles' => $roles,
            'events' => $events,
            'filters' => [
                'q' => $validated['q'] ?? null,
                'role' => $validated['role'] ?? null,
                'event' => $validated['event'] ?? null,
                'from' => $validated['from'] ?? null,
                'to' => $validated['to'] ?? null,
            ],
        ]);
    }
}
