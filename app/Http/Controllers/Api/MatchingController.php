<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserMatch as MatchModel;
use App\Models\InterestSent;
use App\Models\Notification;
use App\Models\DailyTopPick;
use App\Http\Resources\UserResource;
use App\Http\Resources\UserCardResource;
use Illuminate\Support\Facades\Cache;

class MatchingController extends Controller
{
    /**
     * Get profile suggestions based on preferences
     */
    public function getSuggestions(Request $request)
    {
        $user = $request->user();
        $preferences = $user->preferences;

        // Cache suggestions for 2 minutes to reduce database load
        // Cache key includes user ID and page number for proper pagination
        $page = $request->page ?? 1;
        $cacheKey = "suggestions_user_{$user->id}_page_{$page}";

        return Cache::remember($cacheKey, 120, function () use ($user, $preferences, $request) {
            $query = User::with([
                'userProfile.casteModel',
                'userProfile.educationModel',
                'userProfile.occupationModel',
            ])
                ->select('users.id', 'users.matrimony_id', 'users.created_at', 'users.last_login')
                ->where('users.id', '!=', $user->id)
                ->where('users.status', 'active')
                ->whereHas('userProfile', function ($q) {
                    $q->where('is_active_verified', true);
                });

            // Add distance calculation if current user has location
            if ($user->userProfile && $user->userProfile->latitude && $user->userProfile->longitude) {
                $lat = $user->userProfile->latitude;
                $lon = $user->userProfile->longitude;
                $query->join('user_profiles', 'users.id', '=', 'user_profiles.user_id')
                    ->selectRaw("users.*, (6371 * acos(cos(radians(?)) * cos(radians(user_profiles.latitude)) * cos(radians(user_profiles.longitude) - radians(?)) + sin(radians(?)) * sin(radians(user_profiles.latitude)))) AS distance", [$lat, $lon, $lat]);
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

            return UserCardResource::collection($suggestions);
        });
    }

    /**
     * Get today's daily top pick for the user
     */
    public function getDailyTopPick(Request $request)
    {
        $user = $request->user();
        $today = now()->format('Y-m-d');

        // Check if we already have a pick for today
        $pick = DailyTopPick::where('user_id', $user->id)
            ->where('picked_date', $today)
            ->with(['pickedUser.userProfile.casteModel', 'pickedUser.userProfile.educationModel', 'pickedUser.userProfile.occupationModel'])
            ->first();

        if (!$pick) {
            // Generate a new pick
            $pickedUser = $this->generatePickForUser($user);
            
            if (!$pickedUser) {
                return response()->json(['message' => 'No matches found today. Try updating your preferences!'], 404);
            }

            $pick = DailyTopPick::create([
                'user_id' => $user->id,
                'picked_user_id' => $pickedUser->id,
                'picked_date' => $today
            ]);

            // Reload with relations
            $pick->load(['pickedUser.userProfile.casteModel', 'pickedUser.userProfile.educationModel', 'pickedUser.userProfile.occupationModel']);
        }

        return new UserCardResource($pick->pickedUser);
    }

    /**
     * Logic to generate a daily pick for a specific user
     */
    private function generatePickForUser($user)
    {
        $preferences = $user->preferences;
        
        // Basic query for potential matches
        $query = User::where('users.id', '!=', $user->id)
            ->where('users.status', 'active')
            ->whereHas('userProfile', function ($q) use ($user) {
                $q->where('is_active_verified', true);
                
                // Opposite gender logic
                if ($user->userProfile && $user->userProfile->gender) {
                    $targetGender = $user->userProfile->gender === 'male' ? 'female' : 'male';
                    $q->where('gender', $targetGender);
                }
            });

        // Apply basic preferences if available
        if ($preferences) {
            if ($preferences->religion_id) {
                $query->whereHas('userProfile', function ($q) use ($preferences) {
                    $q->where('religion_id', $preferences->religion_id);
                });
            }

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
        }

        // Exclude users who have already received interest or are blocked/blocked by
        $interactedIds = InterestSent::where('sender_id', $user->id)->pluck('receiver_id')->toArray();
        $blockedUserIds = \App\Models\BlockedUser::where('user_id', $user->id)->pluck('blocked_user_id')->toArray();
        $blockedMeIds = \App\Models\BlockedUser::where('blocked_user_id', $user->id)->pluck('user_id')->toArray();
        $alreadyPickedIds = DailyTopPick::where('user_id', $user->id)->pluck('picked_user_id')->toArray();

        $allExcludedIds = array_unique(array_merge(
            [$user->id], 
            $interactedIds, 
            $blockedUserIds, 
            $blockedMeIds,
            $alreadyPickedIds
        ));

        $query->whereNotIn('users.id', $allExcludedIds);

        // Pick one at random from the top active users
        $candidates = $query->orderBy('users.last_login', 'DESC')
            ->limit(20)
            ->get();
            
        return $candidates->isEmpty() ? null : $candidates->random();
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

        // Get IDs of users who are matched
        $matchedUserIds = MatchModel::where('user1_id', $user->id)->pluck('user2_id')
            ->merge(MatchModel::where('user2_id', $user->id)->pluck('user1_id'));

        // Get IDs of users whose contacts are unlocked (either way)
        $unlockedUserIds = \App\Models\ContactUnlock::where('user_id', $user->id)->pluck('unlocked_user_id')
            ->merge(\App\Models\ContactUnlock::where('unlocked_user_id', $user->id)->pluck('user_id'));

        $totalUserIds = $matchedUserIds->merge($unlockedUserIds)->unique();

        $chattableUsers = User::whereIn('id', $totalUserIds)
            ->with([
                'userProfile.casteModel',
                'userProfile.educationModel',
                'userProfile.occupationModel',
            ])
            ->paginate(10);

        // Add distance calculation
        if ($user->userProfile && $user->userProfile->latitude && $user->userProfile->longitude) {
            $lat = $user->userProfile->latitude;
            $lon = $user->userProfile->longitude;

            $chattableUsers->getCollection()->transform(function ($otherUser) use ($lat, $lon) {
                if ($otherUser->userProfile && $otherUser->userProfile->latitude) {
                    $otherUser->distance = $this->calculateDistance(
                        $lat,
                        $lon,
                        $otherUser->userProfile->latitude,
                        $otherUser->userProfile->longitude
                    );
                }
                return $otherUser;
            });
        }

        // Final transformation for the response to match the structure expected by frontend
        $transformed = $chattableUsers->getCollection()->map(function ($otherUser) use ($user) {
            return [
                'id' => $otherUser->id, // Use User ID as a stable reference
                'user1_id' => $user->id,
                'user2_id' => $otherUser->id,
                'status' => 'matched',
                'created_at' => $otherUser->created_at,
                'user' => new UserCardResource($otherUser),
            ];
        });

        $chattableUsers->setCollection($transformed);

        return response()->json([
            'matches' => $chattableUsers
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
                'receiver.userProfile.casteModel',
                'receiver.userProfile.educationModel',
                'receiver.userProfile.occupationModel',
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

        // Final transformation for response
        $interests->getCollection()->transform(function ($interest) {
            return [
                'id' => $interest->id,
                'sender_id' => $interest->sender_id,
                'receiver_id' => $interest->receiver_id,
                'message' => $interest->message,
                'status' => $interest->status,
                'sent_at' => $interest->created_at,
                'responded_at' => $interest->responded_at,
                'receiver' => new UserCardResource($interest->receiver),
            ];
        });

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
                'sender.userProfile.casteModel',
                'sender.userProfile.educationModel',
                'sender.userProfile.occupationModel',
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

        // Final transformation for response
        $interests->getCollection()->transform(function ($interest) use ($user) {
            return [
                'id' => $interest->id,
                'sender_id' => $interest->sender_id,
                'receiver_id' => $interest->receiver_id,
                'message' => $interest->message,
                'status' => $interest->status,
                'sent_at' => $interest->created_at,
                'responded_at' => $interest->responded_at,
                'sender' => new UserCardResource($interest->sender),
            ];
        });

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