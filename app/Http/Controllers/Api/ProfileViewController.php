<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProfileView;
use App\Models\User;
use Carbon\Carbon;

class ProfileViewController extends Controller
{
    /**
     * Get users who viewed the current user's profile
     */
    public function getVisitors(Request $request)
    {
        $user = $request->user();

        // Get unique visitors with their latest view time
        $visitors = ProfileView::where('viewed_profile_id', $user->id)
            ->where('viewer_id', '!=', $user->id) // Exclude self-views
            ->with([
                'viewer.userProfile.religionModel',
                'viewer.userProfile.casteModel',
                'viewer.userProfile.subCasteModel',
                'viewer.userProfile.educationModel',
                'viewer.userProfile.occupationModel'
            ])
            ->select('viewer_id')
            ->selectRaw('MAX(created_at) as last_viewed_at')
            ->groupBy('viewer_id')
            ->orderBy('last_viewed_at', 'desc')
            ->take(20)
            ->get();

        // Transform to return user data directly
        $visitorsData = $visitors->map(function ($view) {
            $viewer = $view->viewer;
            if ($viewer) {
                return [
                    'id' => $viewer->id,
                    'email' => $viewer->email,
                    'user_profile' => $viewer->userProfile,
                    'last_viewed_at' => $view->last_viewed_at,
                ];
            }
            return null;
        })->filter();

        return response()->json([
            'visitors' => $visitorsData->values()
        ]);
    }

    /**
     * Record a profile view
     */
    public function recordView(Request $request, $id)
    {
        $viewer = $request->user();

        // Don't record self-views
        if ($viewer->id == $id) {
            return response()->json(['message' => 'Self view not recorded']);
        }

        // Record the view
        ProfileView::create([
            'viewer_id' => $viewer->id,
            'viewed_profile_id' => $id,
            'viewed_at' => now(), // Still use viewed_at if it's primary timestamp in migration
        ]);

        return response()->json(['message' => 'View recorded successfully']);
    }

    /**
     * Get profiles the current user has visited
     */
    public function getVisitedProfiles(Request $request)
    {
        $user = $request->user();

        $visited = ProfileView::where('viewer_id', $user->id)
            ->where('viewed_profile_id', '!=', $user->id)
            ->with([
                'viewedUser.userProfile.religionModel',
                'viewedUser.userProfile.casteModel',
                'viewedUser.userProfile.subCasteModel',
                'viewedUser.userProfile.educationModel',
                'viewedUser.userProfile.occupationModel'
            ])
            ->select('viewed_profile_id')
            ->selectRaw('MAX(created_at) as last_viewed_at')
            ->groupBy('viewed_profile_id')
            ->orderBy('last_viewed_at', 'desc')
            ->paginate(15);

        return response()->json([
            'visited' => $visited
        ]);
    }

    /**
     * Get profiles whose contact information the current user has unlocked
     */
    public function getContactViewed(Request $request)
    {
        $user = $request->user();

        $unlocked = \App\Models\ContactUnlock::where('user_id', $user->id)
            ->with([
                'unlockedUser.userProfile.religionModel',
                'unlockedUser.userProfile.casteModel',
                'unlockedUser.userProfile.subCasteModel',
                'unlockedUser.userProfile.educationModel',
                'unlockedUser.userProfile.occupationModel'
            ])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'unlocked' => $unlocked
        ]);
    }
}
