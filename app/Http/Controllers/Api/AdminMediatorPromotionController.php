<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MediatorPromotion;
use Illuminate\Support\Facades\Validator;

class AdminMediatorPromotionController extends Controller
{
    /**
     * Get all promotions
     */
    public function index()
    {
        $promotions = MediatorPromotion::with(['user', 'setting'])->orderBy('created_at', 'desc')->get();
        return response()->json([
            'promotions' => $promotions
        ]);
    }

    /**
     * Update promotion (status, counts, payout)
     */
    public function update(Request $request, $id)
    {
        $promotion = MediatorPromotion::with('setting')->find($id);

        if (!$promotion) {
            return response()->json(['error' => 'Promotion not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'views_count' => 'sometimes|integer|min:0',
            'likes_count' => 'sometimes|integer|min:0',
            'comments_count' => 'sometimes|integer|min:0',
            'status' => 'sometimes|string|in:pending,verified,paid,rejected',
            'calculated_payout' => 'sometimes|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation failed', 'messages' => $validator->errors()], 422);
        }

        // Update basic fields
        if ($request->has('views_count'))
            $promotion->views_count = $request->views_count;
        if ($request->has('likes_count'))
            $promotion->likes_count = $request->likes_count;
        if ($request->has('comments_count'))
            $promotion->comments_count = $request->comments_count;
        if ($request->has('status'))
            $promotion->status = $request->status;

        // Auto-calculate payout if counts changed and setting exists
        // Only if admin didn't explicitly provide a payout amount this request? 
        // Or always auto-calc unless overridden?
        // Let's auto-calc if counts provided.
        if (($request->has('views_count') || $request->has('likes_count') || $request->has('comments_count')) && $promotion->setting) {
            $setting = $promotion->setting;

            if ($setting->views_required > 0) {
                $viewsMultiplier = floor($promotion->views_count / $setting->views_required);

                $finalMultiplier = $viewsMultiplier;

                if ($setting->is_likes_enabled && $setting->likes_required > 0) {
                    $likesMultiplier = floor($promotion->likes_count / $setting->likes_required);
                    $finalMultiplier = min($finalMultiplier, $likesMultiplier);
                }

                if ($setting->is_comments_enabled && $setting->comments_required > 0) {
                    $commentsMultiplier = floor($promotion->comments_count / $setting->comments_required);
                    $finalMultiplier = min($finalMultiplier, $commentsMultiplier);
                }

                $promotion->calculated_payout = $finalMultiplier * $setting->payout_amount;
            }
        }

        // Manual override of payout
        if ($request->has('calculated_payout')) {
            $promotion->calculated_payout = $request->calculated_payout;
        }

        if ($request->has('status') && $request->status === 'paid' && !$promotion->paid_at) {
            $promotion->paid_at = now();
        }

        $promotion->save();

        return response()->json([
            'message' => 'Promotion updated successfully',
            'promotion' => $promotion
        ]);
    }

    /**
     * Delete promotion
     */
    public function destroy($id)
    {
        $promotion = MediatorPromotion::find($id);

        if (!$promotion) {
            return response()->json(['error' => 'Promotion not found'], 404);
        }

        $promotion->delete();

        return response()->json([
            'message' => 'Promotion deleted successfully'
        ]);
    }
}
