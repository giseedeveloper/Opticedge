<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AgentDeviceReturn;
use App\Models\RegionalManagerDeviceReturn;
use App\Models\TeamLeaderDeviceReturn;
use App\Services\RegionalManagerDeviceReturnService;
use App\Support\AdminHierarchyReturnList;
use Illuminate\Http\Request;

class DeviceReturnController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');
        if ($status && ! in_array($status, ['pending', 'approved', 'rejected', 'cancelled'], true)) {
            $status = null;
        }

        $returns = AdminHierarchyReturnList::collect($status);

        return view('admin.stock.device-returns-index', compact('returns', 'status'));
    }

    public function showAgentTeamLeader(AgentDeviceReturn $return)
    {
        return $this->renderReturnShow($return, [
            'routeLabel' => 'Agent → Team leader',
            'fromLabel' => 'From (agent)',
            'fromName' => $return->fromAgent?->name ?? '—',
            'fromEmail' => $return->fromAgent?->email,
            'toLabel' => 'To (team leader)',
            'toName' => $return->toTeamLeader?->name ?? '—',
            'toEmail' => $return->toTeamLeader?->email,
            'pendingHint' => $return->isPending() && $return->toTeamLeader
                ? 'Waiting for '.$return->toTeamLeader->name.' to accept or decline.'
                : null,
        ]);
    }

    public function showTeamLeaderRegionalManager(TeamLeaderDeviceReturn $return)
    {
        return $this->renderReturnShow($return, [
            'routeLabel' => 'Team leader → Regional manager',
            'fromLabel' => 'From (team leader)',
            'fromName' => $return->fromTeamLeader?->name ?? '—',
            'fromEmail' => $return->fromTeamLeader?->email,
            'toLabel' => 'To (regional manager)',
            'toName' => $return->toRegionalManager?->name ?? '—',
            'toEmail' => $return->toRegionalManager?->email,
            'pendingHint' => $return->isPending() && $return->toRegionalManager
                ? 'Waiting for '.$return->toRegionalManager->name.' to accept or decline.'
                : null,
        ]);
    }

    public function showRegionalManagerAdmin(RegionalManagerDeviceReturn $return)
    {
        return $this->renderReturnShow($return, [
            'routeLabel' => 'Regional manager → Admin',
            'fromLabel' => 'From (regional manager)',
            'fromName' => $return->fromRegionalManager?->name ?? '—',
            'fromEmail' => $return->fromRegionalManager?->email,
            'toLabel' => 'To',
            'toName' => 'Admin stock',
            'toEmail' => null,
            'pendingHint' => $return->isPending()
                ? 'Admin can accept or decline this return from the list page.'
                : null,
            'canAccept' => $return->isPending(),
            'canDecline' => $return->isPending(),
            'acceptUrl' => route('admin.stock.device-returns.accept', $return),
            'declineUrl' => route('admin.stock.device-returns.decline', $return),
        ]);
    }

    public function accept(Request $request, RegionalManagerDeviceReturn $rmReturn)
    {
        $validated = $request->validate([
            'note' => 'nullable|string|max:2000',
        ]);

        try {
            app(RegionalManagerDeviceReturnService::class)->acceptByAdmin(
                $rmReturn,
                $request->user(),
                $validated['note'] ?? null
            );
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Return accepted. Devices are back in admin stock.');
    }

    public function decline(Request $request, RegionalManagerDeviceReturn $rmReturn)
    {
        $validated = $request->validate([
            'note' => 'nullable|string|max:2000',
        ]);

        try {
            app(RegionalManagerDeviceReturnService::class)->declineByAdmin(
                $rmReturn,
                $request->user(),
                $validated['note'] ?? null
            );
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Return request declined.');
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function renderReturnShow(object $return, array $meta)
    {
        $return->load([
            'decidedByUser',
            'items.productListItem.product.category',
            'items.productListItem.purchase.branch',
            'items.productListItem.stock',
            'items.productListItem.branch',
        ]);

        return view('admin.stock.device-returns-show', array_merge($meta, [
            'returnRequest' => $return,
        ]));
    }
}
