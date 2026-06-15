<?php

namespace Database\Seeders;

use App\Models\RechargeTier;
use Illuminate\Database\Seeder;

class RechargeTierSeeder extends Seeder
{
    public function run(): void
    {
        RechargeTier::insert([
            ['amount' => 199, 'contacts' => 4, 'priority_order' => 0, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['amount' => 499, 'contacts' => 10, 'priority_order' => 1, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['amount' => 999, 'contacts' => 20, 'priority_order' => 2, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
