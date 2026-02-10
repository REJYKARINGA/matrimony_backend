<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserMatch as MatchModel;
use App\Models\InterestSent;
use App\Models\Notification;

class MatchingController extends Controller
{
    /**
     * Get profile suggestions based on preferences
     */
    public function getSuggestions(Request $request)
    {
        $user = $request->user();
        $preferences = $user->preferences;

        $query = User::with(['userProfile', 'profilePhotos'])
            ->where('users.id', '!=', $user->id)
            ->where('users.status', 'active')
            ->whereHas('userProfile', function ($q) {
                $q->where('is_active_verified', true);
            });

        // Add distance calculation if current user has location
        if ($user->userProfile && $user->userProfile->latitude && $user->userProfile->longitude) {
            $lat = $user->userProfile->latitude;
            $lon = $user->userProfile->longitude;
            $query->select('users.*')
                ->join('user_profiles', 'users.id', '=', 'user_profiles.user_id')
                ->selectRaw("(6371 * acos(cos(radians(?)) * cos(radians(user_profiles.latitude)) * cos(radians(user_profiles.longitude) - radians(?)) + sin(radians(?)) * sin(radians(user_profiles.latitude)))) AS distance", [$lat, $lon, $lat]);
        }

        // Apply preferences filter
        if ($preferences) {
            if ($preferences->min_age) {
                $query->whereHas('userProfile', function ($q) use ($preferences) {
                    $q->whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) >= ?', [$preferences->min_age]);
                });
            }

            if ($preferences->max_age) {
                $query->whereHas('userProfile', function ($q) use ($preferences) {
                    $q->whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) <= ?', [$preferences->max_age]);
                });
            }

            if ($preferences->min_height) {
                $query->whereHas('userProfile', function ($q) use ($preferences) {
                    $q->where('height', '>=', $preferences->min_height);
                });
            }

            if ($preferences->max_height) {
                $query->whereHas('userProfile', function ($q) use ($preferences) {
                    $q->where('height', '<=', $preferences->max_height);
                });
            }

            if ($preferences->religion) {
                $query->whereHas('userProfile', function ($q) use ($preferences) {
                    $q->where('religion', $preferences->religion);
                });
            }

            if ($preferences->caste) {
                $query->whereHas('userProfile', function ($q) use ($preferences) {
                    $q->where('caste', $preferences->caste);
                });
            }
        }

        // Exclude already matched or interested users
        $query->whereDoesntHave('matchesAsUser1', function ($q) use ($user) {
            $q->where('user2_id', $user->id);
        })->whereDoesntHave('matchesAsUser2', function ($q) use ($user) {
            $q->where('user1_id', $user->id);
        })->whereDoesntHave('interestsSent', function ($q) use ($user) {
            // Exclude users who have received interest from current user
        });

        $suggestions = $query->latest('users.created_at')->paginate(10);

        return response()->json([
            'suggestions' => $suggestions
        ]);
    }

    /**
     * Create a match between two users
     */
    public function createMatch($userId, Request $request)
    {
        $currentUser = $request->user();
        $targetUser = User::find($userId);

        if (!$targetUser) {
            return response()->json([
                'error' => 'User not found'
            ], 404);
        }

        // Check if match already exists
        $existingMatchQuery = MatchModel::where(function ($query) use ($currentUser, $targetUser) {
            $query->where('user1_id', $currentUser->id)
                ->where('user2_id', $targetUser->id);
        });

        $existingMatch = $existingMatchQuery->orWhere(function ($query) use ($currentUser, $targetUser) {
            $query->where('user1_id', $targetUser->id)
                ->where('user2_id', $currentUser->id);
        })->first();

        if ($existingMatch) {
            return response()->json([
                'error' => 'Match already exists'
            ], 409);
        }

        $match = MatchModel::create([
            'user1_id' => $currentUser->id,
            'user2_id' => $targetUser->id,
            'status' => 'matched'
        ]);

        return response()->json([
            'message' => 'Match created successfully',
            'match' => $match
        ]);
    }

    /**
     * Get user's matches
     */
    public function getMatches(Request $request)
    {
        $user = $request->user();

        $matches = MatchModel::where(function ($q) use ($user) {
            $q->where('user1_id', $user->id)
                ->orWhere('user2_id', $user->id);
        })
            ->with([
                'user1',
                'user1.userProfile',
                'user1.profilePhotos',
                'user2',
                'user2.userProfile',
                'user2.profilePhotos'
            ])
            ->paginate(10);

        // Add distance calculation
        if ($user->userProfile && $user->userProfile->latitude && $user->userProfile->longitude) {
            $lat = $user->userProfile->latitude;
            $lon = $user->userProfile->longitude;

            $matches->getCollection()->transform(function ($match) use ($user, $lat, $lon) {
                $otherUser = $match->user1_id === $user->id ? $match->user2 : $match->user1;
                if ($otherUser && $otherUser->userProfile && $otherUser->userProfile->latitude) {
                    $otherUser->distance = $this->calculateDistance(
                        $lat,
                        $lon,
                        $otherUser->userProfile->latitude,
                        $otherUser->userProfile->longitude
                    );
                }
                return $match;
            });
        }

        return response()->json([
            'matches' => $matches
        ]);
    }

    /**
     * Send interest to another user
     */
    public function sendInterest($userId, Request $request)
    {
        $currentUser = $request->user();
        $targetUser = User::find($userId);

        if (!$targetUser) {
            return response()->json([
                'error' => 'User not found'
            ], 404);
        }

        // Check if interest already sent
        $existingInterest = InterestSent::where('sender_id', $currentUser->id)
            ->where('receiver_id', $targetUser->id)
            ->first();

        if ($existingInterest) {
            return response()->json([
                'error' => 'Interest already sent'
            ], 409);
        }

        $interest = InterestSent::create([
            'sender_id' => $currentUser->id,
            'receiver_id' => $targetUser->id,
            'message' => $request->message ?? null
        ]);

        // Create notification for the receiver
        Notification::create([
            'user_id' => $targetUser->id,
            'sender_id' => $currentUser->id,
            'type' => 'interest',
            'title' => 'New Interest Received',
            'message' => "{$currentUser->userProfile->first_name} {$currentUser->userProfile->last_name} showed interest in your profile",
            'reference_id' => $interest->id
        ]);

        return response()->json([
            'message' => 'Interest sent successfully',
            'interest' => $interest
        ]);
    }

    /**
     * Get sent interests
     */
    public function getSentInterests(Request $request)
    {
        $user = $request->user();

        $interests = InterestSent::where('sender_id', $user->id)
            ->with([
                'receiver',
                'receiver.userProfile',
                'receiver.profilePhotos'
            ])
            ->paginate(10);

        // Add distance calculation
        if ($user->userProfile && $user->userProfile->latitude && $user->userProfile->longitude) {
            $lat = $user->userProfile->latitude;
            $lon = $user->userProfile->longitude;

            $interests->getCollection()->transform(function ($interest) use ($lat, $lon) {
                if ($interest->receiver && $interest->receiver->userProfile && $interest->receiver->userProfile->latitude) {
                    $interest->receiver->distance = $this->calculateDistance(
                        $lat,
                        $lon,
                        $interest->receiver->userProfile->latitude,
                        $interest->receiver->userProfile->longitude
                    );
                }
                return $interest;
            });
        }

        return response()->json([
            'interests' => $interests
        ]);
    }

    /**
     * Get received interests
     */
    public function getReceivedInterests(Request $request)
    {
        $user = $request->user();

        $interests = InterestSent::where('receiver_id', $user->id)
            ->with([
                'sender',
                'sender.userProfile',
                'sender.profilePhotos'
            ])
            ->paginate(10);

        // Add distance calculation
        if ($user->userProfile && $user->userProfile->latitude && $user->userProfile->longitude) {
            $lat = $user->userProfile->latitude;
            $lon = $user->userProfile->longitude;

            $interests->getCollection()->transform(function ($interest) use ($lat, $lon) {
                if ($interest->sender && $interest->sender->userProfile && $interest->sender->userProfile->latitude) {
                    $interest->sender->distance = $this->calculateDistance(
                        $lat,
                        $lon,
                        $interest->sender->userProfile->latitude,
                        $interest->sender->userProfile->longitude
                    );
                }
                return $interest;
            });
        }

        return response()->json([
            'interests' => $interests
        ]);
    }

    /**
     * Accept a received interest
     */
    public function acceptInterest($interestId, Request $request)
    {
        $user = $request->user();
        $interest = InterestSent::where('id', $interestId)
            ->where('receiver_id', $user->id)
            ->first();

        if (!$interest) {
            return response()->json(['error' => 'Interest not found'], 404);
        }

        if ($interest->status !== 'pending') {
            return response()->json(['error' => 'Interest is not pending'], 400);
        }

        $interest->update([
            'status' => 'accepted',
            'responded_at' => now()
        ]);

        // Create a match
        \App\Models\UserMatch::updateOrCreate([
            'user1_id' => min($interest->sender_id, $interest->receiver_id),
            'user2_id' => max($interest->sender_id, $interest->receiver_id),
        ]);

        // Notify the sender
        Notification::create([
            'user_id' => $interest->sender_id,
            'sender_id' => $user->id,
            'type' => 'match',
            'title' => 'Interest Accepted!',
            'message' => "{$user->userProfile->first_name} accepted your interest request. It's a match!",
            'reference_id' => $interest->id
        ]);

        return response()->json([
            'message' => 'Interest accepted and match created',
            'interest' => $interest
        ]);
    }

    /**
     * Reject a received interest
     */
    public function rejectInterest($interestId, Request $request)
    {
        $user = $request->user();
        $interest = InterestSent::where('id', $interestId)
            ->where('receiver_id', $user->id)
            ->first();

        if (!$interest) {
            return response()->json(['error' => 'Interest not found'], 404);
        }

        $interest->update([
            'status' => 'rejected',
            'responded_at' => now()
        ]);

        return response()->json([
            'message' => 'Interest rejected',
            'interest' => $interest
        ]);
    }

    /**
     * Calculate distance between two points using Haversine formula
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}