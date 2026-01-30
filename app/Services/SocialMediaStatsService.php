<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SocialMediaStatsService
{
    public function fetchYouTubeStats($url)
    {
        try {
            $videoId = $this->extractYouTubeVideoId($url);
            if (!$videoId) {
                return null;
            }

            $apiKey = env('YOUTUBE_API_KEY');
            if (!$apiKey) {
                Log::warning('YouTube API key not configured');
                return null;
            }

            $response = Http::get('https://www.googleapis.com/youtube/v3/videos', [
                'part' => 'statistics',
                'id' => $videoId,
                'key' => $apiKey
            ]);

            if ($response->successful() && isset($response->json()['items'][0])) {
                $stats = $response->json()['items'][0]['statistics'];
                return [
                    'views' => $stats['viewCount'] ?? 0,
                    'likes' => $stats['likeCount'] ?? 0,
                    'comments' => $stats['commentCount'] ?? 0
                ];
            }

            return null;
        } catch (\Exception $e) {
            Log::error('YouTube API error: ' . $e->getMessage());
            return null;
        }
    }

    public function fetchInstagramStats($url)
    {
        // Instagram requires Facebook Graph API with Business account
        // Manual verification recommended
        return null;
    }

    private function extractYouTubeVideoId($url)
    {
        // Handle various YouTube URL formats
        $patterns = [
            '/youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)/',
            '/youtu\.be\/([a-zA-Z0-9_-]+)/',
            '/youtube\.com\/embed\/([a-zA-Z0-9_-]+)/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }
}
