<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserProfile;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\UserResource;

class LocationController extends Controller
{
    /**
     * Update authenticated user's location
     */
    public function updateLocation(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $user = $request->user();
        $userProfile = $user->userProfile;

        if (!$userProfile) {
            return response()->json([
                'success' => false,
                'message' => 'User profile not found'
            ], 404);
        }

        $userProfile->latitude = $request->latitude;
        $userProfile->longitude = $request->longitude;
        $userProfile->location_updated_at = Carbon::now();
        $userProfile->save();

        return response()->json([
            'success' => true,
            'message' => 'Location updated successfully',
            'profile' => $userProfile
        ]);
    }

    /**
     * Get nearby users based on current user's location
     */
    public function getNearbyUsers(Request $request)
    {
        $user = $request->user();
        $userProfile = $user->userProfile;
        $radius = $request->radius ?? 50; // default 50km

        if (!$userProfile || !$userProfile->latitude || !$userProfile->longitude) {
            return response()->json([
                'success' => false,
                'message' => 'Your location not set. Please enable location services.',
                'profiles' => [
                    'data' => [],
                    'total' => 0
                ]
            ]);
        }

        $lat = $userProfile->latitude;
        $lon = $userProfile->longitude;

        // Haversine formula to find nearby users
        $query = User::with([
            'userProfile.religionModel',
            'userProfile.casteModel',
            'userProfile.subCasteModel',
            'userProfile.educationModel',
            'userProfile.occupationModel',
            'profilePhotos' => function ($q) {
                $q->where('is_primary', true)->limit(1);
            }
        ])
            ->join('user_profiles', 'users.id', '=', 'user_profiles.user_id')
            ->select('users.*')
            ->selectRaw("
                (6371 * acos(
                    cos(radians(?)) *
                    cos(radians(user_profiles.latitude)) *
                    cos(radians(user_profiles.longitude) - radians(?)) +
                    sin(radians(?)) *
                    sin(radians(user_profiles.latitude))
                )) AS distance
            ", [$lat, $lon, $lat])
            ->where('users.id', '!=', $user->id)
            ->where('users.status', 'active')
            ->whereHas('userProfile', function ($q) {
                $q->where('is_active_verified', true);
            })
            ->whereNotNull('user_profiles.latitude')
            ->whereNotNull('user_profiles.longitude');

        // Filter by gender (show opposite gender)
        if ($userProfile->gender) {
            $oppositeGender = $userProfile->gender === 'male' ? 'female' : ($userProfile->gender === 'female' ? 'male' : null);
            if ($oppositeGender) {
                $query->where('user_profiles.gender', $oppositeGender);
            }
        }

        $nearbyUsers = $query->having('distance', '<=', $radius)
            ->orderBy('distance')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'profiles' => UserResource::collection($nearbyUsers)
        ]);
    }
}
