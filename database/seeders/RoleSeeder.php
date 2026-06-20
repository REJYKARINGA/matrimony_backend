<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'admin', 'label' => 'Super Admin', 'description' => 'Full access to all features'],
            ['name' => 'customer_care', 'label' => 'Customer Care Team', 'description' => 'User management and support'],
            ['name' => 'verification', 'label' => 'Verification Team', 'description' => 'ID and photo verification'],
            ['name' => 'accountant', 'label' => 'Accountant Team', 'description' => 'Financial and payment management'],
            ['name' => 'mediator', 'label' => 'Mediator', 'description' => 'Mediator promotions and payouts'],
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }
    }
}
