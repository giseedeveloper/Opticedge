<?php

namespace App\Services;

use App\Models\Package;
use App\Models\Tenant;
use App\Models\User;
use App\Models\VendorRegistrationIntent;
use Illuminate\Support\Facades\DB;

class TenantSubscriptionService
{
    public function createRenewalIntent(User $admin, Package $package): VendorRegistrationIntent
    {
        $tenant = Tenant::query()->whereKey($admin->tenant_id)->firstOrFail();

        return VendorRegistrationIntent::create([
            'intent_type' => VendorRegistrationIntent::TYPE_RENEWAL,
            'package_id' => $package->id,
            'vendor_name' => $tenant->name,
            'brand_name' => $tenant->brand_name,
            'slug' => $tenant->slug,
            'admin_name' => $admin->name,
            'email' => $admin->email,
            'phone' => $admin->phone ?: '0000000000',
            'password' => null,
            'status' => VendorRegistrationIntent::STATUS_DRAFT,
            'tenant_id' => $tenant->id,
            'user_id' => $admin->id,
        ]);
    }

    /**
     * @return array{tenant: Tenant, user: User}
     */
    public function fulfillRenewal(VendorRegistrationIntent $intent): array
    {
        if ($intent->isCompleted()) {
            return [
                'tenant' => $intent->tenant,
                'user' => $intent->user,
            ];
        }

        return DB::transaction(function () use ($intent) {
            $intent = VendorRegistrationIntent::query()
                ->whereKey($intent->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($intent->isCompleted()) {
                return [
                    'tenant' => $intent->tenant,
                    'user' => $intent->user,
                ];
            }

            $package = $intent->package()->firstOrFail();
            $tenant = Tenant::query()->whereKey($intent->tenant_id)->lockForUpdate()->firstOrFail();
            $user = User::query()->whereKey($intent->user_id)->firstOrFail();

            $tenant->update([
                'package_id' => $package->id,
                'status' => 'active',
                'subscription_ends_at' => $package->subscriptionEndsAtFrom(),
            ]);

            $intent->update([
                'status' => VendorRegistrationIntent::STATUS_COMPLETED,
                'completed_at' => now(),
            ]);

            return [
                'tenant' => $tenant->fresh(),
                'user' => $user->fresh(),
            ];
        });
    }
}
