<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PromotionSetting;
use Illuminate\Support\Facades\Validator;

class AdminPromotionSettingController extends Controller
{
    /**
     * Get all promotion settings
     */
    public function index()
    {
        $settings = PromotionSetting::orderBy('created_at', 'desc')->get();
        return response()->json([
            'settings' => $settings
        ]);
    }

    /**
     * Store a new promotion setting
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'views_required' => 'required|integer|min:1',
            'likes_required' => 'nullable|integer|min:0',
            'comments_required' => 'nullable|integer|min:0',
            'payout_amount' => 'required|numeric|min:0',
            'is_likes_enabled' => 'boolean',
            'is_comments_enabled' => 'boolean',
            'payout_period_days' => 'required|integer|min:1',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation failed', 'messages' => $validator->errors()], 422);
        }

        // If is_default is true, unset other defaults
        if ($request->is_default) {
            PromotionSetting::where('is_default', true)->update(['is_default' => false]);
        }

        $setting = PromotionSetting::create($request->all());

        return response()->json([
            'message' => 'Promotion setting created successfully',
            'setting' => $setting
        ], 201);
    }

    /**
     * Update a promotion setting
     */
    public function update(Request $request, $id)
    {
        $setting = PromotionSetting::find($id);

        if (!$setting) {
            return response()->json(['error' => 'Setting not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'views_required' => 'sometimes|integer|min:1',
            'likes_required' => 'nullable|integer|min:0',
            'comments_required' => 'nullable|integer|min:0',
            'payout_amount' => 'sometimes|numeric|min:0',
            'is_likes_enabled' => 'boolean',
            'is_comments_enabled' => 'boolean',
            'payout_period_days' => 'sometimes|integer|min:1',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation failed', 'messages' => $validator->errors()], 422);
        }

        // If setting as default, unset others
        if ($request->has('is_default') && $request->is_default) {
            PromotionSetting::where('id', '!=', $id)->where('is_default', true)->update(['is_default' => false]);
        }

        $setting->update($request->all());

        return response()->json([
            'message' => 'Promotion setting updated successfully',
            'setting' => $setting
        ]);
    }

    /**
     * Delete a promotion setting
     */
    public function destroy($id)
    {
        $setting = PromotionSetting::find($id);

        if (!$setting) {
            return response()->json(['error' => 'Setting not found'], 404);
        }

        $setting->delete();

        return response()->json([
            'message' => 'Promotion setting deleted successfully'
        ]);
    }

    /**
     * Set a specific setting as default
     */
    public function setDefault($id)
    {
        $setting = PromotionSetting::find($id);

        if (!$setting) {
            return response()->json(['error' => 'Setting not found'], 404);
        }

        // Unset all others
        PromotionSetting::where('id', '!=', $id)->update(['is_default' => false]);

        // Set this one
        $setting->update(['is_default' => true]);

        return response()->json([
            'message' => 'Default setting updated successfully',
            'setting' => $setting
        ]);
    }
}
