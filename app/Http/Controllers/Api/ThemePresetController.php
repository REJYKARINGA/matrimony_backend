<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ThemePreset;
use Illuminate\Support\Facades\Validator;

class ThemePresetController extends Controller
{
    public function index()
    {
        return response()->json([
            'presets' => ThemePreset::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'primary_color' => 'required|string|max:20',
            'secondary_color' => 'required|string|max:20',
            'background_color' => 'required|string|max:20',
            'surface_color' => 'required|string|max:20',
            'text_color' => 'required|string|max:20',
            'gradient_start' => 'required|string|max:20',
            'gradient_end' => 'required|string|max:20',
            'dark_primary' => 'required|string|max:20',
            'dark_secondary' => 'required|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation failed', 'messages' => $validator->errors()], 422);
        }

        $preset = ThemePreset::create($request->all());

        return response()->json([
            'message' => 'Theme preset saved',
            'preset' => $preset,
        ]);
    }

    public function update(Request $request, $id)
    {
        $preset = ThemePreset::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:100',
            'primary_color' => 'sometimes|string|max:20',
            'secondary_color' => 'sometimes|string|max:20',
            'background_color' => 'sometimes|string|max:20',
            'surface_color' => 'sometimes|string|max:20',
            'text_color' => 'sometimes|string|max:20',
            'gradient_start' => 'sometimes|string|max:20',
            'gradient_end' => 'sometimes|string|max:20',
            'dark_primary' => 'sometimes|string|max:20',
            'dark_secondary' => 'sometimes|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation failed', 'messages' => $validator->errors()], 422);
        }

        $preset->update($request->all());

        return response()->json([
            'message' => 'Theme preset updated',
            'preset' => $preset,
        ]);
    }

    public function destroy($id)
    {
        ThemePreset::findOrFail($id)->delete();
        return response()->json(['message' => 'Theme preset deleted']);
    }

    public function apply($id)
    {
        $preset = ThemePreset::findOrFail($id);

        $theme = \App\Models\ThemeSetting::first();
        if (!$theme) {
            $theme = new \App\Models\ThemeSetting();
        }

        $theme->primary_color = $preset->primary_color;
        $theme->secondary_color = $preset->secondary_color;
        $theme->background_color = $preset->background_color;
        $theme->surface_color = $preset->surface_color;
        $theme->text_color = $preset->text_color;
        $theme->gradient_start = $preset->gradient_start;
        $theme->gradient_end = $preset->gradient_end;
        $theme->dark_primary = $preset->dark_primary;
        $theme->dark_secondary = $preset->dark_secondary;
        $theme->save();

        return response()->json([
            'message' => "Theme preset '{$preset->name}' applied",
            'theme' => $theme,
        ]);
    }
}
