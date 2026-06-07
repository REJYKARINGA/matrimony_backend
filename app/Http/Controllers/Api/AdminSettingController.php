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
                'user_contact_permission_unlock' => false,
                'mandatory_permission_for_unlock' => false,
                'free_unlock_enabled' => false,
                'free_unlock_expires_at' => null,
            ]);
        }
        return response()->json(['setting' => $setting]);
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'daily_contact_unlock_limit' => 'sometimes|integer|min:0',
            'user_contact_permission_unlock' => 'sometimes|boolean',
            'mandatory_permission_for_unlock' => 'sometimes|boolean',
            'free_unlock_enabled' => 'sometimes|boolean',
            'free_unlock_expires_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation failed', 'messages' => $validator->errors()], 422);
        }

        $setting = AdminSetting::first();
        if (!$setting) {
            $setting = AdminSetting::create($request->only([
                'daily_contact_unlock_limit',
                'user_contact_permission_unlock',
                'mandatory_permission_for_unlock',
                'free_unlock_enabled',
                'free_unlock_expires_at',
            ]));
        } else {
            $setting->update($request->only([
                'daily_contact_unlock_limit',
                'user_contact_permission_unlock',
                'mandatory_permission_for_unlock',
                'free_unlock_enabled',
                'free_unlock_expires_at',
            ]));
        }

        return response()->json([
            'message' => 'Admin settings updated successfully',
            'setting' => $setting
        ]);
    }
}
