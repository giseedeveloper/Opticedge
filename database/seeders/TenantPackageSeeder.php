<?php

namespace Database\Seeders;

use App\Models\Package;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class TenantPackageSeeder extends Seeder
{
    public function run(): void
    {
        $package = Package::firstOrCreate(
            ['slug' => 'standard'],
            [
                'name' => 'Standard',
                'price' => 150000,
                'interval' => 'monthly',
                'profit' => 50000,
                'features_json' => ['command_center' => false, 'multi_branch' => true],
                'max_users' => 50,
                'description' => 'Default tenant package',
                'is_active' => true,
            ]
        );

        Tenant::updateOrCreate(
            ['id' => 1],
            [
                'name' => 'Optic Edge Africa',
                'slug' => 'optic-edge-africa',
                'brand_name' => 'OpticEdge Africa',
                'status' => 'active',
                'package_id' => $package->id,
            ]
        );
    }
}
