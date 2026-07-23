<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Selcompay;
use App\Models\WalletTransaction;
use App\Services\WalletService;
use App\Services\WalletTopupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin → Pay out: top up the vendor's disbursement wallet (money in via Selcom
 * Checkout) and view the wallet ledger. The wallet funds agent-commission payouts.
 */
class WalletController extends Controller
{
    public function __construct(
        protected WalletTopupService $topups,
        protected WalletService $wallet,
    ) {
    }

    public function deposit(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'amount' => 'required|integer|min:500|max:100000000',
            'phone' => 'required|string|max:20',
        ]);

        $tenantId = $this->tenantId($request);
        if ($tenantId <= 0) {
            return redirect()->route('admin.payout.index')->withErrors(['selcom' => 'Your account is not linked to a vendor.']);
        }

        $result = $this->topups->initiate(
            $tenantId,
            (int) $data['amount'],
            $data['phone'],
            ['email' => $request->user()->email, 'name' => $request->user()->name],
            (int) $request->user()->id,
        );

        if (! $result['ok']) {
            return redirect()->route('admin.payout.index')->withErrors(['selcom' => $result['message']]);
        }

        if (($result['status'] ?? null) === 'pending' && $result['selcompay']) {
            return redirect()->route('admin.payout.wallet.wait', $result['selcompay']);
        }

        return redirect()->route('admin.payout.index')->with('success', $result['message']);
    }

    public function wait(Selcompay $selcompay): View
    {
        $this->ensureTopup($selcompay);

        return view('admin.payout.wallet-wait', compact('selcompay'));
    }

    public function status(Selcompay $selcompay): JsonResponse
    {
        $this->ensureTopup($selcompay);

        return response()->json($this->topups->checkStatus($selcompay));
    }

    public function ledger(Request $request): View
    {
        $tenantId = $this->tenantId($request);

        $balance = $tenantId > 0 ? $this->wallet->balance($tenantId) : 0.0;

        $transactions = WalletTransaction::query()
            ->where('tenant_id', $tenantId)
            ->latest('id')
            ->paginate(50)
            ->withQueryString();

        return view('admin.payout.wallet-ledger', compact('balance', 'transactions'));
    }

    protected function tenantId(Request $request): int
    {
        return (int) ($request->user()->tenant_id ?? \App\Support\TenantContext::id() ?? 0);
    }

    protected function ensureTopup(Selcompay $selcompay): void
    {
        if ($selcompay->purpose !== Selcompay::PURPOSE_WALLET_TOPUP) {
            abort(404);
        }

        // A vendor may only watch their own top-up.
        if ((int) $selcompay->payout_source_id !== (int) (auth()->user()->tenant_id ?? 0)) {
            abort(403);
        }
    }
}
