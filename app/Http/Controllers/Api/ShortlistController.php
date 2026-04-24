<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserCardResource;
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
            ->whereHas('shortlistedUser')
            ->with(['shortlistedUser.userProfile.casteModel', 'shortlistedUser.userProfile.educationModel', 'shortlistedUser.userProfile.occupationModel'])
            ->paginate(10);

        $shortlisted->getCollection()->transform(function ($item) {
            $resource = new UserCardResource($item->shortlistedUser);
            // Re-map the resource data to a clean array
            $item->shortlisted_user_data = $resource->toArray(request());
            return $item;
        });

        // Add distance calculation
        if ($user->userProfile && $user->userProfile->latitude && $user->userProfile->longitude) {
            $lat = $user->userProfile->latitude;
            $lon = $user->userProfile->longitude;

            $shortlisted->getCollection()->transform(function ($item) use ($lat, $lon) {
                if ($item->shortlistedUser && $item->shortlistedUser->userProfile && $item->shortlistedUser->userProfile->latitude) {
                    $item->shortlistedUser->distance = $this->calculateDistance(
                        $lat,
                        $lon,
                        $item->shortlistedUser->userProfile->latitude,
                        $item->shortlistedUser->userProfile->longitude
                    );
                }
                return $item;
            });
        }

        return response()->json([
            'shortlist' => [
                'data' => $shortlisted->getCollection()->map(function($item) {
                    return [
                        'id' => $item->id,
                        'user_id' => $item->user_id,
                        'shortlisted_user_id' => $item->shortlisted_user_id,
                        'notes' => $item->notes,
                        'shortlisted_user' => $item->shortlisted_user_data,
                    ];
                }),
                'current_page' => $shortlisted->currentPage(),
                'last_page' => $shortlisted->lastPage(),
                'total' => $shortlisted->total(),
            ]
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
