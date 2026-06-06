<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\User;
use App\Models\VendorRegistrationIntent;
use App\Services\VendorSubscriptionPaymentService;
use App\Support\PlatformPaymentSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class VendorSubscribeApiController extends Controller
{
    public function packages()
    {
        return app(PublicShopApiController::class)->packages();
    }

    public function storeIntent(Request $request)
    {
        $validated = $request->validate([
            'package_slug' => 'required|string|exists:packages,slug',
            'vendor_name' => 'required|string|max:255',
            'brand_name' => 'nullable|string|max:255',
            'admin_name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'phone' => 'required|string|max:32',
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $package = Package::query()->where('slug', $validated['package_slug'])->where('is_active', true)->firstOrFail();

        $intent = VendorRegistrationIntent::create([
            'package_id' => $package->id,
            'vendor_name' => $validated['vendor_name'],
            'brand_name' => $validated['brand_name'] ?: $validated['vendor_name'],
            'slug' => Str::slug($validated['vendor_name']),
            'admin_name' => $validated['admin_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => $validated['password'],
            'status' => VendorRegistrationIntent::STATUS_DRAFT,
        ]);

        return response()->json([
            'message' => 'Registration saved.',
            'data' => $this->formatIntent($intent->load('package')),
        ], 201);
    }

    public function startPayment(Request $request, VendorRegistrationIntent $intent, VendorSubscriptionPaymentService $payments)
    {
        if ($intent->isCompleted()) {
            return response()->json([
                'message' => 'Subscription already completed.',
                'data' => $this->formatIntent($intent->load('package')),
            ]);
        }

        $validated = $request->validate([
            'payment_phone' => PlatformPaymentSettings::isVendorSubscriptionDemo()
                ? 'nullable|string|max:32'
                : 'required|string|max:32',
        ]);

        $paymentPhone = $validated['payment_phone'] ?? $intent->phone;

        try {
            $payments->normalizePhone($paymentPhone);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        try {
            $payments->initiatePayment($intent, $paymentPhone);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $intent->refresh();

        return response()->json([
            'message' => $intent->isCompleted()
                ? 'Subscription completed.'
                : 'Payment initiated. Approve the request on your phone.',
            'data' => $this->formatIntent($intent->load('package')),
            'is_demo' => $payments->isDemoMode(),
        ]);
    }

    public function status(VendorRegistrationIntent $intent, VendorSubscriptionPaymentService $payments)
    {
        $result = $payments->checkAndFulfill($intent->fresh());

        $payload = [
            'status' => $result['status'],
            'message' => $result['message'] ?? '',
            'intent' => $this->formatIntent($intent->fresh()->load(['package', 'tenant', 'user'])),
        ];

        if ($result['status'] === 'completed') {
            $payload['credentials'] = [
                'email' => $intent->email,
                'login_hint' => 'Use the password you chose during registration to sign in.',
            ];
        }

        return response()->json($payload);
    }

    protected function formatIntent(VendorRegistrationIntent $intent): array
    {
        return [
            'id' => $intent->id,
            'status' => $intent->status,
            'vendor_name' => $intent->vendor_name,
            'brand_name' => $intent->brand_name,
            'admin_name' => $intent->admin_name,
            'email' => $intent->email,
            'phone' => $intent->phone,
            'payment_phone' => $intent->payment_phone,
            'package' => $intent->relationLoaded('package') && $intent->package ? [
                'id' => $intent->package->id,
                'slug' => $intent->package->slug,
                'name' => $intent->package->name,
                'price' => (float) $intent->package->price,
            ] : null,
            'completed_at' => $intent->completed_at?->toIso8601String(),
        ];
    }
}
