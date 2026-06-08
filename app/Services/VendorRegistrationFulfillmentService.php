<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\User;
use App\Models\VendorRegistrationIntent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class VendorRegistrationFulfillmentService
{
    public function __construct(
        protected TenantSubscriptionService $tenantSubscriptions,
    ) {}

    /**
     * Create tenant + admin user after successful subscription payment.
     *
     * @return array{tenant: Tenant, user: User}
     */
    public function fulfill(VendorRegistrationIntent $intent): array
    {
        if ($intent->isRenewal()) {
            return $this->tenantSubscriptions->fulfillRenewal($intent);
        }

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

            $slug = $this->uniqueSlug($intent->slug ?: Str::slug($intent->vendor_name));

            $tenant = Tenant::create([
                'name' => $intent->vendor_name,
                'slug' => $slug,
                'brand_name' => $intent->brand_name ?: $intent->vendor_name,
                'status' => 'active',
                'package_id' => $package->id,
                'subscription_ends_at' => $package->subscriptionEndsAtFrom(),
            ]);

            $user = $this->createAdminUser($intent, $tenant->id);

            $intent->update([
                'status' => VendorRegistrationIntent::STATUS_COMPLETED,
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'completed_at' => now(),
            ]);

            return compact('tenant', 'user');
        });
    }

    private function uniqueSlug(string $base): string
    {
        $slug = Str::slug($base) ?: 'vendor';
        $candidate = $slug;
        $i = 1;

        while (Tenant::where('slug', $candidate)->exists()) {
            $candidate = $slug.'-'.$i++;
        }

        return $candidate;
    }

    private function createAdminUser(VendorRegistrationIntent $intent, int $tenantId): User
    {
        $attributes = [
            'name' => $intent->admin_name,
            'email' => $intent->email,
            'phone' => $intent->phone,
            'role' => 'admin',
            'status' => 'active',
            'tenant_id' => $tenantId,
            'email_verified_at' => now(),
        ];

        if (! Hash::isHashed($intent->password)) {
            return User::create([...$attributes, 'password' => $intent->password]);
        }

        $user = User::create($attributes);
        DB::table('users')->where('id', $user->id)->update(['password' => $intent->password]);

        return $user->fresh();
    }
}
