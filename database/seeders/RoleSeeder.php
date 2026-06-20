<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'admin', 'label' => 'Super Admin', 'description' => 'Full access to all features', 'sort_order' => 1],
            ['name' => 'customer_care', 'label' => 'Customer Care Team', 'description' => 'User management and support', 'sort_order' => 2],
            ['name' => 'verification', 'label' => 'Verification Team', 'description' => 'ID and photo verification', 'sort_order' => 3],
            ['name' => 'accountant', 'label' => 'Accountant Team', 'description' => 'Financial and payment management', 'sort_order' => 4],
            ['name' => 'mediator', 'label' => 'Mediator', 'description' => 'Mediator promotions and payouts', 'sort_order' => 5],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(['name' => $role['name']], $role);
        }
    }
}
