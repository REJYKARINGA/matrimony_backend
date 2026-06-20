<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index()
    {
        return response()->json([
            'roles' => Role::orderBy('sort_order')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'label' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
        ]);

        $role = Role::create($data);

        return response()->json(['role' => $role, 'message' => 'Role created successfully'], 201);
    }

    public function update(Request $request, $id)
    {
        $role = Role::findOrFail($id);

        $data = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $id,
            'label' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
        ]);

        $role->update($data);

        return response()->json(['role' => $role, 'message' => 'Role updated successfully']);
    }

    public function destroy($id)
    {
        $role = Role::findOrFail($id);
        if ($role->name === 'admin') {
            return response()->json(['error' => 'Cannot delete the admin role'], 422);
        }
        $role->menus()->detach();
        $role->delete();

        return response()->json(['message' => 'Role deleted successfully']);
    }

    public function reorder(Request $request)
    {
        $data = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|integer|exists:roles,id',
            'items.*.sort_order' => 'required|integer',
        ]);

        foreach ($data['items'] as $item) {
            Role::where('id', $item['id'])->update(['sort_order' => $item['sort_order']]);
        }

        return response()->json(['message' => 'Roles reordered successfully']);
    }
}
