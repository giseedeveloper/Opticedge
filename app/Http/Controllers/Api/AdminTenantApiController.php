<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\Tenant;
use App\Models\VendorRegistrationIntent;
use App\Services\TenantSubscriptionService;
use App\Services\VendorSubscriptionPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminTenantApiController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $tenant = $this->resolveTenant($request);
        $tenant->load('package:id,name,price,interval');

        $package = $tenant->package;

        return response()->json([
            'data' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'brand_name' => $tenant->brand_name,
                'package_id' => $package?->id,
                'package_slug' => $package?->slug,
                'package_name' => $package?->name,
                'billing' => $package
                    ? $package->formattedPrice().' / '.$package->intervalLabel()
                    : null,
                'status' => $tenant->status,
                'subscription_ends_at' => $tenant->subscription_ends_at?->toIso8601String(),
                'subscription_ends_at_formatted' => $tenant->subscription_ends_at?->format('M j, Y'),
            ],
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $tenant = $this->resolveTenant($request);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:tenants,slug,'.$tenant->id,
            'brand_name' => 'nullable|string|max:255',
        ]);

        $tenant->update([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'brand_name' => $validated['brand_name'] ?? $validated['name'],
        ]);

        return response()->json([
            'message' => 'Vendor profile updated.',
            'data' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'brand_name' => $tenant->brand_name,
            ],
        ]);
    }

    public function subscribe(Request $request, Package $package, TenantSubscriptionService $subscriptions, VendorSubscriptionPaymentService $payments): JsonResponse
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
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Payment initiated.',
            'data' => $this->formatIntent($intent->fresh()->load('package')),
        ]);
    }

    public function subscriptionStatus(VendorRegistrationIntent $intent, VendorSubscriptionPaymentService $payments): JsonResponse
    {
        $user = request()->user();
        abort_unless($user && $user->role === 'admin', 403);
        abort_unless($intent->isRenewal(), 404);
        abort_unless((int) $intent->tenant_id === (int) $user->tenant_id, 403);
        abort_unless((int) $intent->user_id === (int) $user->id, 403);

        $result = $payments->checkAndFulfill($intent->fresh());

        return response()->json($result);
    }

    private function formatIntent(VendorRegistrationIntent $intent): array
    {
        return [
            'id' => $intent->id,
            'status' => $intent->status,
            'vendor_name' => $intent->vendor_name,
            'package' => $intent->relationLoaded('package') && $intent->package ? [
                'id' => $intent->package->id,
                'name' => $intent->package->name,
                'slug' => $intent->package->slug,
                'price' => (float) $intent->package->price,
            ] : null,
            'payment_phone' => $intent->payment_phone,
            'completed_at' => $intent->completed_at?->toIso8601String(),
        ];
    }

    private function resolveTenant(Request $request): Tenant
    {
        $user = $request->user();
        if (! $user?->tenant_id) {
            abort(403, 'Your account is not linked to a vendor.');
        }

        return Tenant::query()->whereKey($user->tenant_id)->firstOrFail();
    }
}
