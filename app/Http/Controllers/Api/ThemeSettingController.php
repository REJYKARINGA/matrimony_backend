<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ThemeSetting;
use Illuminate\Support\Facades\Validator;

class ThemeSettingController extends Controller
{
    public function index()
    {
        $theme = ThemeSetting::first();
        if (!$theme) {
            $theme = ThemeSetting::create([
                'primary_color' => '#00C897',
                'secondary_color' => '#00A87D',
                'background_color' => '#F5FBF9',
                'surface_color' => '#FFFFFF',
                'text_color' => '#212121',
                'gradient_start' => '#00C897',
                'gradient_end' => '#00A87D',
                'dark_primary' => '#42A5F5',
                'dark_secondary' => '#64B5F6',
            ]);
        }
        return response()->json(['theme' => $theme]);
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
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

        $allowed = [
            'primary_color', 'secondary_color', 'background_color',
            'surface_color', 'text_color', 'gradient_start',
            'gradient_end', 'dark_primary', 'dark_secondary',
        ];

        $theme = ThemeSetting::first();
        if (!$theme) {
            $theme = ThemeSetting::create($request->only($allowed));
        } else {
            $theme->update($request->only($allowed));
        }

        return response()->json([
            'message' => 'Theme settings updated successfully',
            'theme' => $theme,
        ]);
    }

    public function getPublic()
    {
        $theme = ThemeSetting::first();
        if (!$theme) {
            return response()->json([
                'primary_color' => '#00C897',
                'secondary_color' => '#00A87D',
                'background_color' => '#F5FBF9',
                'surface_color' => '#FFFFFF',
                'text_color' => '#212121',
                'gradient_start' => '#00C897',
                'gradient_end' => '#00A87D',
                'dark_primary' => '#42A5F5',
                'dark_secondary' => '#64B5F6',
            ]);
        }
        return response()->json([
            'primary_color' => $theme->primary_color,
            'secondary_color' => $theme->secondary_color,
            'background_color' => $theme->background_color,
            'surface_color' => $theme->surface_color,
            'text_color' => $theme->text_color,
            'gradient_start' => $theme->gradient_start,
            'gradient_end' => $theme->gradient_end,
            'dark_primary' => $theme->dark_primary,
            'dark_secondary' => $theme->dark_secondary,
        ]);
    }
}
