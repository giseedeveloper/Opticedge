<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RegionalManagerProductTransfer;
use App\Models\TeamLeaderProductTransfer;
use App\Support\AdminHierarchyTransferList;
use Illuminate\Http\Request;

class DeviceTransferController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');
        if ($status && ! in_array($status, ['pending', 'approved', 'rejected', 'cancelled'], true)) {
            $status = null;
        }

        $transfers = AdminHierarchyTransferList::collect($status);

        return view('admin.stock.device-transfers-index', compact('transfers', 'status'));
    }

    public function showAdminRegionalManager(RegionalManagerProductTransfer $transfer)
    {
        $transfer->load([
            'createdByAdmin',
            'toRegionalManager',
            'decidedByUser',
            'items.productListItem.product.category',
            'items.productListItem.purchase.branch',
            'items.productListItem.stock',
            'items.productListItem.branch',
        ]);

        return view('admin.stock.device-transfers-show', [
            'transfer' => $transfer,
            'routeLabel' => 'Admin → Regional manager',
            'fromLabel' => 'From (admin)',
            'fromName' => $transfer->createdByAdmin?->name ?? 'Admin',
            'fromEmail' => $transfer->createdByAdmin?->email,
            'toLabel' => 'To (regional manager)',
            'toName' => $transfer->toRegionalManager?->name ?? '—',
            'toEmail' => $transfer->toRegionalManager?->email,
            'pendingHint' => $transfer->isPending() && $transfer->toRegionalManager
                ? 'Waiting for '.$transfer->toRegionalManager->name.' to accept or decline.'
                : null,
        ]);
    }

    public function showRegionalManagerTeamLeader(TeamLeaderProductTransfer $transfer)
    {
        $transfer->load([
            'fromRegionalManager',
            'toTeamLeader',
            'decidedByUser',
            'items.productListItem.product.category',
            'items.productListItem.purchase.branch',
            'items.productListItem.stock',
            'items.productListItem.branch',
        ]);

        return view('admin.stock.device-transfers-show', [
            'transfer' => $transfer,
            'routeLabel' => 'Regional manager → Team leader',
            'fromLabel' => 'From (regional manager)',
            'fromName' => $transfer->fromRegionalManager?->name ?? '—',
            'fromEmail' => $transfer->fromRegionalManager?->email,
            'toLabel' => 'To (team leader)',
            'toName' => $transfer->toTeamLeader?->name ?? '—',
            'toEmail' => $transfer->toTeamLeader?->email,
            'pendingHint' => $transfer->isPending() && $transfer->toTeamLeader
                ? 'Waiting for '.$transfer->toTeamLeader->name.' to accept or decline.'
                : null,
        ]);
    }
}
