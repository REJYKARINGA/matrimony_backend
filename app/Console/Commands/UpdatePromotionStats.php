<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MediatorPromotion;
use App\Services\SocialMediaStatsService;

class UpdatePromotionStats extends Command
{
    protected $signature = 'promotions:update-stats';
    protected $description = 'Update promotion statistics from social media platforms';

    protected $statsService;

    public function __construct(SocialMediaStatsService $statsService)
    {
        parent::__construct();
        $this->statsService = $statsService;
    }

    public function handle()
    {
        $promotions = MediatorPromotion::whereIn('status', ['pending', 'verified'])
            ->with('setting')
            ->get();

        $updated = 0;

        foreach ($promotions as $promotion) {
            $stats = null;

            if ($promotion->platform === 'youtube') {
                $stats = $this->statsService->fetchYouTubeStats($promotion->link);
            } elseif ($promotion->platform === 'instagram') {
                $stats = $this->statsService->fetchInstagramStats($promotion->link);
            }

            if ($stats) {
                $promotion->update([
                    'views_count' => $stats['views'],
                    'likes_count' => $stats['likes'],
                    'comments_count' => $stats['comments'],
                ]);

                // Recalculate payout
                if ($promotion->setting) {
                    $this->calculatePayout($promotion, $promotion->setting);
                }

                $updated++;
            }
        }

        $this->info("Updated stats for {$updated} promotions.");
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
