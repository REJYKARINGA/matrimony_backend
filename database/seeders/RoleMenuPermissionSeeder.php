<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Menu;
use Illuminate\Database\Seeder;

class RoleMenuPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $admin = Role::where('name', 'admin')->first();
        if ($admin) {
            $admin->menus()->sync(Menu::pluck('id'));
        }
    }
}
