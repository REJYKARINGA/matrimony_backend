<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    public function index()
    {
        return response()->json([
            'menus' => Menu::orderBy('sort_order')->orderBy('group')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'path' => 'required|string|max:255|unique:menus,path',
            'label' => 'required|string|max:255',
            'group' => 'required|string|max:255',
            'icon' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer',
        ]);

        $menu = Menu::create($data);

        return response()->json(['menu' => $menu, 'message' => 'Menu created successfully'], 201);
    }

    public function update(Request $request, $id)
    {
        $menu = Menu::findOrFail($id);

        $data = $request->validate([
            'path' => 'required|string|max:255|unique:menus,path,' . $id,
            'label' => 'required|string|max:255',
            'group' => 'required|string|max:255',
            'icon' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer',
        ]);

        $menu->update($data);

        return response()->json(['menu' => $menu, 'message' => 'Menu updated successfully']);
    }

    public function destroy($id)
    {
        $menu = Menu::findOrFail($id);
        $menu->roles()->detach();
        $menu->delete();

        return response()->json(['message' => 'Menu deleted successfully']);
    }

    public function reorder(Request $request)
    {
        $data = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|integer|exists:menus,id',
            'items.*.sort_order' => 'required|integer',
        ]);

        foreach ($data['items'] as $item) {
            Menu::where('id', $item['id'])->update(['sort_order' => $item['sort_order']]);
        }

        return response()->json(['message' => 'Menus reordered successfully']);
    }
}
