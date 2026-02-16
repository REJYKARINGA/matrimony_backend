<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserMatch as MatchModel;
use App\Models\InterestSent;
use App\Models\Notification;
use App\Http\Resources\UserResource;

class MatchingController extends Controller
{
    /**
     * Get profile suggestions based on preferences
     */
    public function getSuggestions(Request $request)
    {
        $user = $request->user();
        $preferences = $user->preferences;

        $query = User::with([
            'userProfile.religionModel',
            'userProfile.casteModel',
            'userProfile.subCasteModel',
            'userProfile.educationModel',
            'userProfile.occupationModel',
            'profilePhotos'
        ])
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

            if ($preferences->religion_id) {
                $query->whereHas('userProfile', function ($q) use ($preferences) {
                    $q->where('religion_id', $preferences->religion_id);
                });
            }

            if ($preferences->caste_ids && is_array($preferences->caste_ids)) {
                $query->whereHas('userProfile', function ($q) use ($preferences) {
                    $q->whereIn('caste_id', $preferences->caste_ids);
                });
            }

            if ($preferences->marital_status) {
                $query->whereHas('userProfile', function ($q) use ($preferences) {
                    $q->where('marital_status', $preferences->marital_status);
                });
            }

            if ($preferences->drug_addiction && $preferences->drug_addiction != 'any') {
                $query->whereHas('userProfile', function ($q) use ($preferences) {
                    $q->where('drug_addiction', $preferences->drug_addiction == 'yes');
                });
            }

            if ($preferences->smoke && is_array($preferences->smoke)) {
                $query->whereHas('userProfile', function ($q) use ($preferences) {
                    $q->whereIn('smoke', $preferences->smoke);
                });
            }

            if ($preferences->alcohol && is_array($preferences->alcohol)) {
                $query->whereHas('userProfile', function ($q) use ($preferences) {
                    $q->whereIn('alcohol', $preferences->alcohol);
                });
            }
        }

        // Exclude users who have already received interest or are blocked/blocked by
        $excludedUserIds = InterestSent::where('sender_id', $user->id)->pluck('receiver_id')->toArray();
        $blockedUserIds = \App\Models\BlockedUser::where('user_id', $user->id)->pluck('blocked_user_id')->toArray();
        $blockedMeIds = \App\Models\BlockedUser::where('blocked_user_id', $user->id)->pluck('user_id')->toArray();

        $allExcludedIds = array_unique(array_merge([$user->id], $excludedUserIds, $blockedUserIds, $blockedMeIds));

        $query->whereNotIn('users.id', $allExcludedIds);

        // Primary sort by login date (not time) so we can randomize within the day
        // to provide fresh content on every refresh while keeping active users on top.
        $suggestions = $query->orderByRaw('DATE(users.last_login) DESC')
            ->inRandomOrder()
            ->paginate(12);

        return response()->json([
            'suggestions' => UserResource::collection($suggestions)
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
                'user1.userProfile.religionModel',
                'user1.userProfile.casteModel',
                'user1.userProfile.subCasteModel',
                'user1.userProfile.educationModel',
                'user1.userProfile.occupationModel',
                'user1.profilePhotos',
                'user2',
                'user2.userProfile.religionModel',
                'user2.userProfile.casteModel',
                'user2.userProfile.subCasteModel',
                'user2.userProfile.educationModel',
                'user2.userProfile.occupationModel',
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
            'matches' => $matches->map(function ($match) use ($user) {
                return [
                    'id' => $match->id,
                    'user1_id' => $match->user1_id,
                    'user2_id' => $match->user2_id,
                    'status' => $match->status,
                    'created_at' => $match->created_at,
                    'user' => new UserResource($match->user1_id === $user->id ? $match->user2 : $match->user1),
                ];
            })
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
                'receiver.userProfile.religionModel',
                'receiver.userProfile.casteModel',
                'receiver.userProfile.subCasteModel',
                'receiver.userProfile.educationModel',
                'receiver.userProfile.occupationModel',
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
            'interests' => $interests->map(function ($interest) {
                return [
                    'id' => $interest->id,
                    'sender_id' => $interest->sender_id,
                    'receiver_id' => $interest->receiver_id,
                    'message' => $interest->message,
                    'status' => $interest->status,
                    'sent_at' => $interest->created_at,
                    'responded_at' => $interest->responded_at,
                    'receiver' => new UserResource($interest->receiver),
                ];
            })
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
                'sender.userProfile.religionModel',
                'sender.userProfile.casteModel',
                'sender.userProfile.subCasteModel',
                'sender.userProfile.educationModel',
                'sender.userProfile.occupationModel',
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
            'interests' => $interests->map(function ($interest) use ($user) {
                return [
                    'id' => $interest->id,
                    'sender_id' => $interest->sender_id,
                    'receiver_id' => $interest->receiver_id,
                    'message' => $interest->message,
                    'status' => $interest->status,
                    'sent_at' => $interest->created_at,
                    'responded_at' => $interest->responded_at,
                    'sender' => new UserResource($interest->sender),
                ];
            })
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