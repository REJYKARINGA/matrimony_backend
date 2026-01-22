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
            ->where('id', '!=', $user->id)
            ->where('status', 'active')
            ->whereHas('userProfile', function($q) {
                $q->where('is_active_verified', true);
            });


        // Apply preferences filter
        // if ($preferences) {
        //     if ($preferences->min_age) {
        //         $query->whereHas('userProfile', function ($q) use ($preferences) {
        //             $q->whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) >= ?', [$preferences->min_age]);
        //         });
        //     }

        //     if ($preferences->max_age) {
        //         $query->whereHas('userProfile', function ($q) use ($preferences) {
        //             $q->whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) <= ?', [$preferences->max_age]);
        //         });
        //     }

        //     if ($preferences->min_height) {
        //         $query->whereHas('userProfile', function ($q) use ($preferences) {
        //             $q->where('height', '>=', $preferences->min_height);
        //         });
        //     }

        //     if ($preferences->max_height) {
        //         $query->whereHas('userProfile', function ($q) use ($preferences) {
        //             $q->where('height', '<=', $preferences->max_height);
        //         });
        //     }

        //     if ($preferences->religion) {
        //         $query->whereHas('userProfile', function ($q) use ($preferences) {
        //             $q->where('religion', $preferences->religion);
        //         });
        //     }

        //     if ($preferences->caste) {
        //         $query->whereHas('userProfile', function ($q) use ($preferences) {
        //             $q->where('caste', $preferences->caste);
        //         });
        //     }
        // }

        // // Exclude already matched or interested users
        // $query->whereDoesntHave('matchesAsUser1', function ($q) use ($user) {
        //     $q->where('user2_id', $user->id);
        // })->whereDoesntHave('matchesAsUser2', function ($q) use ($user) {
        //     $q->where('user1_id', $user->id);
        // })->whereDoesntHave('interestsSent', function ($q) use ($user) {
        //     // Exclude users who have received interest from current user
        // });

        $suggestions = $query->paginate(10);

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
                'user1:id,email',
                'user1.userProfile:first_name,last_name,profile_picture',
                'user2:id,email',
                'user2.userProfile:first_name,last_name,profile_picture'
            ])
            ->paginate(10);

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
                'receiver:id,email',
                'receiver.userProfile:first_name,last_name,profile_picture'
            ])
            ->paginate(10);

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
                'sender:id,email',
                'sender.userProfile:first_name,last_name,profile_picture'
            ])
            ->paginate(10);

        return response()->json([
            'interests' => $interests
        ]);
    }
}