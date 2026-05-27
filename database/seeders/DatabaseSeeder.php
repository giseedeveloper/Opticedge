<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(TenantPackageSeeder::class);
        $this->call(PaymentChannelSeeder::class);
        $this->call(TanzaniaRegionSeeder::class);
        $this->call(BrandModelSeeder::class);

        User::factory()->create([
            'name' => 'Platform Superadmin',
            'email' => 'superadmin@opticedgeafrica.com',
            'password' => bcrypt('password'),
            'role' => 'superadmin',
            'tenant_id' => null,
        ]);

        User::factory()->create([
            'name' => 'Tenant Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'tenant_id' => 1,
        ]);
    }
}
