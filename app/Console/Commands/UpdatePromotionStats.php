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
        // Include 'paid' promotions so they can be re-evaluated for new views
        $promotions = MediatorPromotion::whereIn('status', ['pending', 'verified', 'paid'])
            ->with('setting')
            ->get();

        $updated = 0;

        foreach ($promotions as $promotion) {
            /** @var MediatorPromotion $promotion */
            $stats = null;

            if ($promotion->platform === 'youtube') {
                $stats = $this->statsService->fetchYouTubeStats($promotion->link);
            } elseif ($promotion->platform === 'instagram') {
                $stats = $this->statsService->fetchInstagramStats($promotion->link, $promotion->username);
            }

            if ($stats) {
                // Use explicit assignment and save to avoid mass assignment issues or lint warnings
                $promotion->views_count = intval($stats['views']);
                $promotion->likes_count = intval($stats['likes']);
                $promotion->comments_count = intval($stats['comments']);
                $promotion->save();

                // Recalculate payout with fresh data
                if ($promotion->setting) {
                    $this->calculatePayout($promotion->fresh(), $promotion->setting);
                }

                $updated++;
            }
        }

        $this->info("Updated stats for {$updated} promotions.");
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
            $newPendingPayout = max(0, $totalEarned - $promotion->total_paid_amount);

            $promotion->calculated_payout = $newPendingPayout;

            // If there's a new payout pending and status was 'paid', move it back to 'verified'
            if ($newPendingPayout > 0 && $promotion->status === 'paid') {
                $promotion->status = 'verified';
            }

            $promotion->save();
        }
    }
}
