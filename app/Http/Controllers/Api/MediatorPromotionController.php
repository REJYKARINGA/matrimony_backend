<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MediatorPromotion;
use App\Models\PromotionSetting;
use App\Services\SocialMediaStatsService;
use Illuminate\Support\Facades\Validator;

class MediatorPromotionController extends Controller
{
    protected $statsService;

    public function __construct(SocialMediaStatsService $statsService)
    {
        $this->statsService = $statsService;
    }

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

        // Fetch stats from platform
        $stats = null;
        if ($request->platform === 'youtube') {
            $stats = $this->statsService->fetchYouTubeStats($request->link);
        } elseif ($request->platform === 'instagram') {
            $stats = $this->statsService->fetchInstagramStats($request->link);
        }

        $promotion = MediatorPromotion::create([
            'user_id' => $request->user()->id,
            'promotion_setting_id' => $setting ? $setting->id : null,
            'platform' => $request->platform,
            'link' => $request->link,
            'username' => $request->username,
            'status' => 'pending',
            'views_count' => $stats['views'] ?? 0,
            'likes_count' => $stats['likes'] ?? 0,
            'comments_count' => $stats['comments'] ?? 0,
            'total_paid_amount' => 0,
            'calculated_payout' => 0,
        ]);

        // Calculate payout if stats were fetched
        if ($stats && $setting) {
            $this->calculatePayout($promotion, $setting);
        }

        return response()->json([
            'message' => 'Promotion submitted successfully',
            'promotion' => $promotion->load('setting'),
            'stats_fetched' => $stats !== null
        ], 201);
    }

    private function calculatePayout($promotion, $setting)
    {
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

            $totalEarned = $finalMultiplier * $setting->payout_amount;

            // Calculate pending payout (Total earned - what has already been paid)
            $promotion->calculated_payout = max(0, $totalEarned - $promotion->total_paid_amount);
            $promotion->save();
        }
    }
}
