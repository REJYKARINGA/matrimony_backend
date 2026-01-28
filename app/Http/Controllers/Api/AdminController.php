<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\UserVerification;
use App\Models\UserProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    /**
     * Get pending verifications
     */
    public function getPendingVerifications()
    {
        $verifications = UserVerification::with('user.userProfile')
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json($verifications);
    }

    /**
     * Approve verification
     */
    public function approveVerification(Request $request, $id)
    {
        $verification = UserVerification::findOrFail($id);

        DB::transaction(function () use ($verification, $request) {
            // Update verification status
            $verification->update([
                'status' => 'verified',
                'verified_at' => now(),
                'verified_by' => $request->user()->id,
                'rejection_reason' => null
            ]);

            // Update user profile verified flag
            // Ensure UserProfile exists
            $userProfile = UserProfile::where('user_id', $verification->user_id)->first();
            if ($userProfile) {
                $userProfile->update(['is_active_verified' => true]);
            }
        });

        return response()->json([
            'message' => 'User verified successfully',
            'verification' => $verification
        ]);
    }

    /**
     * Reject verification
     */
    public function rejectVerification(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string'
        ]);

        $verification = UserVerification::findOrFail($id);

        $verification->update([
            'status' => 'rejected',
            'rejection_reason' => $request->reason,
            'verified_at' => null, // reset if previously set
            'verified_by' => $request->user()->id
        ]);

        // Also ensure profile is NOT verified
        $userProfile = UserProfile::where('user_id', $verification->user_id)->first();
        if ($userProfile) {
            $userProfile->update(['is_active_verified' => false]);
        }

        return response()->json([
            'message' => 'Verification rejected',
            'verification' => $verification
        ]);
    }
    /**
     * Get all users
     */
    public function getUsers(Request $request)
    {
        $query = User::with('userProfile')
            ->where('role', '!=', 'admin');

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                    ->orWhere('matrimony_id', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhereHas('userProfile', function ($q) use ($search) {
                        $q->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    });
            });
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(15);
        return response()->json($users);
    }

    /**
     * Toggle block/unblock user
     */
    public function toggleBlockUser($id)
    {
        $user = User::findOrFail($id);
        $user->status = $user->status === 'active' ? 'blocked' : 'active';
        $user->save();

        return response()->json([
            'message' => 'User status updated successfully',
            'user' => $user
        ]);
    }

    /**
     * Get all user profiles
     */
    public function getUserProfiles(Request $request)
    {
        $query = UserProfile::with('user')
            ->join('users', 'user_profiles.user_id', '=', 'users.id')
            ->where('users.role', '!=', 'admin');

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('user_profiles.first_name', 'like', "%{$search}%")
                    ->orWhere('user_profiles.last_name', 'like', "%{$search}%")
                    ->orWhere('user_profiles.religion', 'like', "%{$search}%")
                    ->orWhere('user_profiles.education', 'like', "%{$search}%")
                    ->orWhere('user_profiles.occupation', 'like', "%{$search}%")
                    ->orWhere('users.matrimony_id', 'like', "%{$search}%")
                    ->orWhere('users.email', 'like', "%{$search}%");
            });
        }

        $profiles = $query->select('user_profiles.*')
            ->orderBy('user_profiles.created_at', 'desc')
            ->paginate(15);

        return response()->json($profiles);
    }

    /**
     * Get all reports
     */
    public function getReports()
    {
        $reports = \App\Models\Report::with(['reporter.userProfile', 'reportedUser.userProfile'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        return response()->json($reports);
    }

    /**
     * Resolve report
     */
    public function resolveReport(Request $request, $id)
    {
        $report = \App\Models\Report::findOrFail($id);
        $report->status = 'resolved';
        $report->resolution_notes = $request->resolution_notes;
        $report->reviewed_by = $request->user()->id;
        $report->reviewed_at = now();
        $report->save();

        return response()->json(['message' => 'Report resolved', 'report' => $report]);
    }

    /**
     * Get success stories
     */
    public function getSuccessStories()
    {
        $stories = \App\Models\SuccessStory::with(['user1.userProfile', 'user2.userProfile'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        return response()->json($stories);
    }

    /**
     * Approve success story
     */
    public function approveSuccessStory(Request $request, $id)
    {
        $story = \App\Models\SuccessStory::findOrFail($id);
        $story->is_approved = true;
        $story->approved_by = $request->user()->id;
        $story->approved_at = now();
        $story->save();

        return response()->json(['message' => 'Success story approved', 'story' => $story]);
    }

    /**
     * Reject success story
     */
    public function rejectSuccessStory($id)
    {
        $story = \App\Models\SuccessStory::findOrFail($id);
        $story->delete(); // Or update status if soft delete is preferred
        return response()->json(['message' => 'Success story rejected']);
    }

    /**
     * Get payments
     */
    public function getPayments()
    {
        $payments = \App\Models\Payment::with(['user.userProfile'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        return response()->json($payments);
    }
}
