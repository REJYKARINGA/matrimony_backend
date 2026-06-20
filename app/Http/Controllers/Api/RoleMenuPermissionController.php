<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Menu;
use App\Models\RoleMenuPermission;
use Illuminate\Http\Request;

class RoleMenuPermissionController extends Controller
{
    public function getByRoleName($roleName)
    {
        $role = Role::where('name', $roleName)->first();

        if (!$role) {
            return response()->json([
                'role' => $roleName,
                'paths' => $roleName === 'admin' ? null : [],
            ]);
        }

        $paths = $role->menus()->pluck('menus.path');

        if ($paths->isEmpty()) {
            return response()->json([
                'role' => $roleName,
                'paths' => $role->name === 'admin' ? null : [],
            ]);
        }

        return response()->json([
            'role' => $roleName,
            'paths' => $paths,
        ]);
    }

    public function index()
    {
        $roles = Role::with('menus')->orderBy('sort_order')->get();
        $menus = Menu::orderBy('sort_order')->orderBy('group')->get()->groupBy('group');

        return response()->json([
            'roles' => $roles,
            'menus' => $menus,
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'permissions' => 'required|array',
            'permissions.*.role_id' => 'required|exists:roles,id',
            'permissions.*.menu_ids' => 'array',
            'permissions.*.menu_ids.*' => 'exists:menus,id',
        ]);

        foreach ($data['permissions'] as $perm) {
            $role = Role::findOrFail($perm['role_id']);
            $role->menus()->sync($perm['menu_ids'] ?? []);
        }

        return response()->json(['message' => 'Permissions updated successfully']);
    }
}
