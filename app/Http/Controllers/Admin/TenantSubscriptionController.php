<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\Tenant;
use App\Models\VendorRegistrationIntent;
use App\Services\TenantSubscriptionService;
use App\Services\VendorSubscriptionPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TenantSubscriptionController extends Controller
{
    public function subscribe(Request $request, Package $package, TenantSubscriptionService $subscriptions, VendorSubscriptionPaymentService $payments): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user && $user->role === 'admin' && $user->tenant_id, 403);

        $tenant = Tenant::query()->whereKey($user->tenant_id)->firstOrFail();
        abort_unless($tenant->isSuspended(), 403, 'Subscription renewal is only available while your vendor account is suspended.');

        abort_unless($package->is_active, 404);

        $validated = $request->validate([
            'payment_phone' => 'required|string|max:32',
        ]);

        $intent = $subscriptions->createRenewalIntent($user, $package);

        try {
            $payments->initiatePayment($intent, $validated['payment_phone']);
        } catch (\Throwable $e) {
            return redirect()
                ->route('admin.tenant.edit')
                ->withErrors(['payment_phone' => $e->getMessage()]);
        }

        return redirect()->route('admin.tenant.subscribe.processing', $intent);
    }

    public function processing(VendorRegistrationIntent $intent, VendorSubscriptionPaymentService $payments): View|RedirectResponse
    {
        $this->authorizeRenewalIntent($intent);

        if ($intent->isCompleted()) {
            return redirect()->route('admin.tenant.subscribe.success', $intent);
        }

        if ($intent->status !== VendorRegistrationIntent::STATUS_PAYMENT_PENDING) {
            return redirect()->route('admin.tenant.edit');
        }

        return view('admin.tenant.payment-processing', [
            'intent' => $intent->load('package'),
            'paymentPhone' => $intent->payment_phone,
            'isDemoPayment' => $payments->isDemoMode(),
        ]);
    }

    public function status(VendorRegistrationIntent $intent, VendorSubscriptionPaymentService $payments): JsonResponse
    {
        $this->authorizeRenewalIntent($intent);

        $result = $payments->checkAndFulfill($intent->fresh());

        return response()->json($result);
    }

    public function success(VendorRegistrationIntent $intent, VendorSubscriptionPaymentService $payments): View|RedirectResponse
    {
        $this->authorizeRenewalIntent($intent);

        $intent->load(['package', 'tenant', 'user']);

        if (! $intent->isCompleted()) {
            $payments->checkAndFulfill($intent);
            $intent->refresh();
        }

        if (! $intent->isCompleted()) {
            return redirect()
                ->route('admin.tenant.subscribe.processing', $intent)
                ->with('info', 'Complete payment on your phone to restore your account.');
        }

        return view('admin.tenant.subscribe-success', compact('intent'));
    }

    private function authorizeRenewalIntent(VendorRegistrationIntent $intent): void
    {
        $user = auth()->user();

        abort_unless($user && $user->role === 'admin', 403);
        abort_unless($intent->isRenewal(), 404);
        abort_unless((int) $intent->tenant_id === (int) $user->tenant_id, 403);
        abort_unless((int) $intent->user_id === (int) $user->id, 403);
    }
}
