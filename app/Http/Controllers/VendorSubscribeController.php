<?php

namespace App\Http\Controllers;

use App\Models\VendorRegistrationIntent;
use App\Services\VendorSubscriptionPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\View\View;

class VendorSubscribeController extends Controller
{
    public function processing(VendorRegistrationIntent $intent, VendorSubscriptionPaymentService $payments): View|RedirectResponse
    {
        if ($intent->isCompleted()) {
            return redirect()->route('vendor.subscribe.success', $intent);
        }

        if ($intent->status !== VendorRegistrationIntent::STATUS_PAYMENT_PENDING) {
            return redirect()->route('vendor.subscribe', $intent->package);
        }

        return view('vendor-subscribe.payment-processing', [
            'intent' => $intent->load('package'),
            'paymentPhone' => $intent->payment_phone,
            'isDemoPayment' => $payments->isDemoMode(),
        ]);
    }

    public function status(VendorRegistrationIntent $intent, VendorSubscriptionPaymentService $payments): JsonResponse
    {
        $result = $payments->checkAndFulfill($intent->fresh());

        if ($result['status'] === 'completed') {
            $plainPassword = null;
            $sessionKey = 'vendor_subscribe_plain_password.'.$intent->id;
            if (session()->has($sessionKey)) {
                try {
                    $plainPassword = Crypt::decryptString(session($sessionKey));
                    session()->forget($sessionKey);
                } catch (\Throwable) {
                    $plainPassword = null;
                }
            }

            session()->flash('vendor_admin_credentials', [
                'email' => $intent->fresh()->email,
                'password' => $plainPassword,
                'login_url' => route('login'),
            ]);
        }

        return response()->json($result);
    }

    public function success(Request $request, VendorRegistrationIntent $intent): View|RedirectResponse
    {
        $intent->load(['package', 'tenant', 'user']);

        if (! $intent->isCompleted()) {
            $payments = app(VendorSubscriptionPaymentService::class);
            $payments->checkAndFulfill($intent);
            $intent->refresh();
        }

        $credentials = session('vendor_admin_credentials');

        if (! $intent->isCompleted() && ! $credentials) {
            return redirect()
                ->route('vendor.subscribe.processing', $intent)
                ->with('info', 'Complete payment on your phone to activate your account.');
        }

        if ($intent->isCompleted() && ! $credentials) {
            session()->flash('vendor_admin_credentials', [
                'email' => $intent->email,
                'password' => null,
                'login_url' => route('login'),
                'note' => 'Your vendor account is active. Use the password you chose during registration to sign in.',
            ]);
            $credentials = session('vendor_admin_credentials');
        }

        return view('vendor-subscribe.success', [
            'intent' => $intent,
            'credentials' => $credentials,
        ]);
    }
}
