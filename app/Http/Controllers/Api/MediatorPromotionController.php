<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MediatorPromotion;
use App\Models\PromotionSetting;
use Illuminate\Support\Facades\Validator;

class MediatorPromotionController extends Controller
{
    /**
     * Get my submitted promotions
     */
    public function index(Request $request)
    {
        $promotions = MediatorPromotion::where('user_id', $request->user()->id)
            ->with('setting')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'promotions' => $promotions
        ]);
    }

    /**
     * Submit a new promotion
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'platform' => 'required|string|max:255',
            'link' => 'required|url',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation failed', 'messages' => $validator->errors()], 422);
        }

        // Get default setting
        $setting = PromotionSetting::where('is_default', true)->first();

        // If no default setting, maybe pick the latest active one or fail?
        // For now, let's allow submitting without setting (admin can assign later) or fail.
        // User said "select the payout one of them as default". Assuming one exists.

        $promotion = MediatorPromotion::create([
            'user_id' => $request->user()->id,
            'promotion_setting_id' => $setting ? $setting->id : null,
            'platform' => $request->platform,
            'link' => $request->link,
            'status' => 'pending',
            'views_count' => 0,
            'likes_count' => 0,
            'comments_count' => 0,
            'calculated_payout' => 0,
        ]);

        return response()->json([
            'message' => 'Promotion submitted successfully',
            'promotion' => $promotion
        ], 201);
    }
}
