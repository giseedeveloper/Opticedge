<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RegionalManagerDeviceReturn;
use App\Services\RegionalManagerDeviceReturnService;
use Illuminate\Http\Request;

class DeviceReturnController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');
        $q = RegionalManagerDeviceReturn::query()
            ->with(['fromRegionalManager', 'items.productListItem.product.category', 'decidedByUser'])
            ->latest();

        if ($status && in_array($status, ['pending', 'approved', 'rejected', 'cancelled'], true)) {
            $q->where('status', $status);
        }

        $returns = $q->get();

        return view('admin.stock.device-returns-index', compact('returns', 'status'));
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
}
