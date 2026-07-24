<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Selcompay;
use App\Services\SelcomBusinessDisbursementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Admin → Pay out: LIVE agent commission disbursement via the Selcom Business API.
 * Money leaves the platform wallet and lands in the agent's mobile-money wallet.
 */
class CommissionBusinessPayoutController extends Controller
{
    public function __construct(
        protected SelcomBusinessDisbursementService $service,
    ) {
    }

    /**
     * Pay a single commission line.
     */
    public function pay(string $source, int $id): RedirectResponse
    {
        if (! in_array($source, ['credit', 'sale'], true)) {
            return redirect()->route('admin.payout.index')->withErrors(['selcom' => 'Invalid payout source.']);
        }

        $result = $this->service->disburse($source, $id);

        if (! $result['ok']) {
            return redirect()->route('admin.payout.index')->withErrors(['selcom' => $result['message']]);
        }

        $selcompay = $result['selcompay'];

        if ($selcompay && $selcompay->payment_status === 'pending') {
            return redirect()->route('admin.payout.business.wait', $selcompay);
        }

        return redirect()->route('admin.payout.index')->with('success', $result['message']);
    }

    /**
     * Pay all eligible commission lines in the background (queued job).
     */
    public function bulkStart(): RedirectResponse
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? \App\Support\TenantContext::id() ?? 0);
        if ($tenantId <= 0) {
            return redirect()
                ->route('admin.payout.index')
                ->withErrors(['selcom' => 'Your account is not linked to a vendor.']);
        }

        $items = $this->service->collectEligibleLineItems();
        $result = $this->service->queueDisburseLines($tenantId, $items, auth()->id());

        if (! $result['ok']) {
            return redirect()
                ->route('admin.payout.index')
                ->withErrors(['selcom' => $result['message']]);
        }

        return redirect()
            ->route('admin.payout.index')
            ->with('success', $result['message'])
            ->with('bulk_selcom_summary', [
                'queued' => true,
                'candidates' => $result['candidates'],
                'started' => 0,
                'skipped' => 0,
                'failures' => [],
                'message' => $result['message'],
            ]);
    }

    public function wait(Selcompay $selcompay): View
    {
        $this->ensureDisbursement($selcompay);

        return view('admin.payout.business-wait', compact('selcompay'));
    }

    public function status(Selcompay $selcompay): JsonResponse
    {
        $this->ensureDisbursement($selcompay);

        return response()->json($this->service->refreshStatus($selcompay));
    }

    protected function ensureDisbursement(Selcompay $selcompay): void
    {
        if ($selcompay->purpose !== Selcompay::PURPOSE_AGENT_COMMISSION_DISBURSE) {
            abort(404);
        }
    }
}
