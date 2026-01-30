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
            'status' => 'pending',
            'views_count' => $stats['views'] ?? 0,
            'likes_count' => $stats['likes'] ?? 0,
            'comments_count' => $stats['comments'] ?? 0,
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
        $meetsRequirements = $promotion->views_count >= $setting->views_required;

        if ($setting->is_likes_enabled) {
            $meetsRequirements = $meetsRequirements && ($promotion->likes_count >= $setting->likes_required);
        }

        if ($setting->is_comments_enabled) {
            $meetsRequirements = $meetsRequirements && ($promotion->comments_count >= $setting->comments_required);
        }

        if ($meetsRequirements) {
            $promotion->calculated_payout = $setting->payout_amount;
            $promotion->save();
        }
    }
}
