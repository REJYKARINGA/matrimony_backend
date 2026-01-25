<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ShortlistedProfile;
use App\Models\User;

class ShortlistController extends Controller
{
    /**
     * Get user's shortlisted profiles
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $shortlisted = ShortlistedProfile::where('user_id', $user->id)
            ->with([
                'shortlistedUser:id,email',
                'shortlistedUser.userProfile:user_id,first_name,last_name,profile_picture,date_of_birth,gender,city,state'
            ])
            ->paginate(10);

        return response()->json([
            'shortlisted' => $shortlisted
        ]);
    }

    /**
     * Add a profile to shortlist
     */
    public function store(Request $request)
    {
        $request->validate([
            'shortlisted_user_id' => 'required|exists:users,id',
        ]);

        $currentUser = $request->user();
        $targetUserId = $request->shortlisted_user_id;

        if ($currentUser->id == $targetUserId) {
            return response()->json(['error' => 'You cannot shortlist yourself'], 400);
        }

        $existing = ShortlistedProfile::where('user_id', $currentUser->id)
            ->where('shortlisted_user_id', $targetUserId)
            ->first();

        if ($existing) {
            return response()->json(['message' => 'Profile already shortlisted'], 200);
        }

        $shortlist = ShortlistedProfile::create([
            'user_id' => $currentUser->id,
            'shortlisted_user_id' => $targetUserId,
            'notes' => $request->notes
        ]);

        // Create notification for the shortlisted user
        \App\Models\Notification::create([
            'user_id' => $targetUserId,
            'sender_id' => $currentUser->id,
            'type' => 'shortlist',
            'title' => 'Profile Shortlisted',
            'message' => "{$currentUser->userProfile->first_name} shortlisted your profile",
            'reference_id' => $shortlist->id
        ]);

        return response()->json([
            'message' => 'Profile shortlisted successfully',
            'shortlist' => $shortlist
        ], 201);
    }

    /**
     * Remove a profile from shortlist
     */
    public function destroy($shortlistedUserId, Request $request)
    {
        $user = $request->user();

        $deleted = ShortlistedProfile::where('user_id', $user->id)
            ->where('shortlisted_user_id', $shortlistedUserId)
            ->delete();

        if ($deleted) {
            return response()->json(['message' => 'Profile removed from shortlist']);
        }

        return response()->json(['error' => 'Shortlist entry not found'], 404);
    }

    /**
     * Check if a profile is shortlisted
     */
    public function check($shortlistedUserId, Request $request)
    {
        $user = $request->user();

        $isShortlisted = ShortlistedProfile::where('user_id', $user->id)
            ->where('shortlisted_user_id', $shortlistedUserId)
            ->exists();

        return response()->json(['is_shortlisted' => $isShortlisted]);
    }
}
