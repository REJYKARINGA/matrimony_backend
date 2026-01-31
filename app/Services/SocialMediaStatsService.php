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

    public function fetchInstagramStats($url, $providedUsername = null)
    {
        try {
            $shortcode = $this->extractInstagramShortcode($url);
            if (!$shortcode) {
                return null;
            }

            $accessToken = env('INSTAGRAM_ACCESS_TOKEN');
            $businessId = env('INSTAGRAM_BUSINESS_ACCOUNT_ID');

            if (!$accessToken) {
                return null;
            }

            // Step 1: Determine the author's username
            $authorName = $providedUsername ?: $this->extractUsernameFromUrl($url);

            if (!$authorName) {
                $response = Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
                ])->get($url);

                if ($response->successful()) {
                    if (preg_match('/"username":"([^"]+)"/', $response->body(), $matches)) {
                        $authorName = $matches[1];
                    }
                }
            }

            // Step 2: Use Business Discovery for real stats
            if ($businessId && $authorName) {
                $discoveryResponse = Http::get("https://graph.facebook.com/v19.0/{$businessId}", [
                    'fields' => "business_discovery.username({$authorName}){media{shortcode,like_count,comments_count,video_view_count}}",
                    'access_token' => $accessToken
                ]);

                if ($discoveryResponse->successful()) {
                    $mediaItems = $discoveryResponse->json()['business_discovery']['media']['data'] ?? [];
                    foreach ($mediaItems as $media) {
                        if ($media['shortcode'] === $shortcode) {
                            return [
                                'views' => $media['video_view_count'] ?? 0,
                                'likes' => $media['like_count'] ?? 0,
                                'comments' => $media['comments_count'] ?? 0,
                                'is_live' => true
                            ];
                        }
                    }
                }
            }

            // Fallback
            return [
                'views' => 0,
                'likes' => 0,
                'comments' => 0,
                'verified' => true,
                'note' => 'Automatic stats pending'
            ];
        } catch (\Exception $e) {
            Log::error('Instagram API error: ' . $e->getMessage());
            return null;
        }
    }

    private function extractUsernameFromUrl($url)
    {
        // Pattern: instagram.com/USERNAME/p/SHORTCODE
        if (preg_match('/instagram\.com\/([a-zA-Z0-9._]+)\/(?:p|reel|reels)\//', $url, $matches)) {
            // Check if matches[1] isn't just 'p' or 'reel'
            if (!in_array($matches[1], ['p', 'reel', 'reels'])) {
                return $matches[1];
            }
        }
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

    private function extractInstagramShortcode($url)
    {
        $patterns = [
            '/(?:https?:\/\/)?(?:www\.)?instagram\.com\/(?:p|reel)\/([a-zA-Z0-9_-]+)/',
            '/(?:https?:\/\/)?(?:www\.)?instagram\.com\/reels\/([a-zA-Z0-9_-]+)/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }
}
