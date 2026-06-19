<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AdminSetting;
use Illuminate\Support\Facades\Validator;

class AdminSettingController extends Controller
{
    public function index()
    {
        $setting = AdminSetting::first();
        if (!$setting) {
            $setting = AdminSetting::create([
                'daily_contact_unlock_limit' => 10,
                'contact_unlock_price' => 49.00,
                'user_contact_permission_unlock' => false,
                'mandatory_permission_for_unlock' => false,
                'free_unlock_enabled' => false,
                'free_unlock_expires_at' => null,
                'wallet_is_active' => true,
                'wallet_in_maintenance_ios' => false,
                'wallet_in_maintenance_android' => false,
                'review_enabled' => true,
                'review_unlock_threshold' => 10,
                'review_min_days_between' => 90,
                'review_max_prompts' => 3,
                'theme_primary_color' => '#00C897',
                'theme_secondary_color' => '#00A87D',
                'theme_background_color' => '#F5FBF9',
                'theme_surface_color' => '#FFFFFF',
                'theme_text_color' => '#212121',
                'theme_gradient_start' => '#00C897',
                'theme_gradient_end' => '#00A87D',
                'theme_dark_primary' => '#42A5F5',
                'theme_dark_secondary' => '#64B5F6',
            ]);
        }
        return response()->json(['setting' => $setting]);
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'daily_contact_unlock_limit' => 'sometimes|integer|min:0',
            'contact_unlock_price' => 'sometimes|numeric|min:0',
            'user_contact_permission_unlock' => 'sometimes|boolean',
            'mandatory_permission_for_unlock' => 'sometimes|boolean',
            'free_unlock_enabled' => 'sometimes|boolean',
            'free_unlock_expires_at' => 'nullable|date',
            'wallet_is_active' => 'sometimes|boolean',
            'wallet_in_maintenance_ios' => 'sometimes|boolean',
            'wallet_in_maintenance_android' => 'sometimes|boolean',
            'review_enabled' => 'sometimes|boolean',
            'review_unlock_threshold' => 'sometimes|integer|min:1',
            'review_min_days_between' => 'sometimes|integer|min:0',
            'review_max_prompts' => 'sometimes|integer|min:1',
            'theme_primary_color' => 'sometimes|string|max:20',
            'theme_secondary_color' => 'sometimes|string|max:20',
            'theme_background_color' => 'sometimes|string|max:20',
            'theme_surface_color' => 'sometimes|string|max:20',
            'theme_text_color' => 'sometimes|string|max:20',
            'theme_gradient_start' => 'sometimes|string|max:20',
            'theme_gradient_end' => 'sometimes|string|max:20',
            'theme_dark_primary' => 'sometimes|string|max:20',
            'theme_dark_secondary' => 'sometimes|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation failed', 'messages' => $validator->errors()], 422);
        }

        $allowed = [
            'daily_contact_unlock_limit',
            'contact_unlock_price',
            'user_contact_permission_unlock',
            'mandatory_permission_for_unlock',
            'free_unlock_enabled',
            'free_unlock_expires_at',
            'wallet_is_active',
            'wallet_in_maintenance_ios',
            'wallet_in_maintenance_android',
            'review_enabled',
            'review_unlock_threshold',
            'review_min_days_between',
            'review_max_prompts',
            'theme_primary_color',
            'theme_secondary_color',
            'theme_background_color',
            'theme_surface_color',
            'theme_text_color',
            'theme_gradient_start',
            'theme_gradient_end',
            'theme_dark_primary',
            'theme_dark_secondary',
        ];

        $setting = AdminSetting::first();
        if (!$setting) {
            $setting = AdminSetting::create($request->only($allowed));
        } else {
            $setting->update($request->only($allowed));
        }

        return response()->json([
            'message' => 'Admin settings updated successfully',
            'setting' => $setting
        ]);
    }
}
