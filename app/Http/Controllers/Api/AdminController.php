<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\UserVerification;
use App\Models\UserProfile;
use App\Models\User;
use App\Models\FamilyDetail;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    /**
     * Get pending verifications
     */
    public function getPendingVerifications()
    {
        $verifications = UserVerification::with(['user.userProfile', 'user.profilePhotos'])
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
        $query = UserProfile::with(['user.verification'])
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

    /**
     * Get all family details
     */
    public function getFamilyDetails(Request $request)
    {
        $query = FamilyDetail::with(['user.userProfile'])
            ->join('users', 'family_details.user_id', '=', 'users.id')
            ->where('users.role', '!=', 'admin');

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('father_name', 'like', "%{$search}%")
                    ->orWhere('mother_name', 'like', "%{$search}%")
                    ->orWhere('family_location', 'like', "%{$search}%")
                    ->orWhere('father_occupation', 'like', "%{$search}%")
                    ->orWhere('mother_occupation', 'like', "%{$search}%")
                    ->orWhereHas('user.userProfile', function ($subQuery) use ($search) {
                        $subQuery->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    });
            });
        }

        $familyDetails = $query->select('family_details.*', 'users.id as user_id', 'users.matrimony_id')
            ->orderBy('family_details.created_at', 'desc')
            ->paginate(15);

        return response()->json($familyDetails);
    }

    /**
     * Get dashboard statistics
     */
    public function getDashboardStats()
    {
        try {
            // User Statistics
            $totalUsers = User::where('role', '!=', 'admin')->count();
            $activeUsers = User::where('role', '!=', 'admin')
                ->where('status', 'active')
                ->count();
            $blockedUsers = User::where('role', '!=', 'admin')
                ->where('status', 'blocked')
                ->count();

            // User growth over last 12 months
            $userGrowth = [];
            for ($i = 11; $i >= 0; $i--) {
                $month = now()->subMonths($i);
                $count = User::where('role', '!=', 'admin')
                    ->whereYear('created_at', $month->year)
                    ->whereMonth('created_at', $month->month)
                    ->count();
                $userGrowth[] = [
                    'month' => $month->format('M Y'),
                    'count' => $count
                ];
            }

            // Verification Statistics
            $pendingVerifications = UserVerification::where('status', 'pending')->count();
            $approvedVerifications = UserVerification::where('status', 'verified')->count();
            $rejectedVerifications = UserVerification::where('status', 'rejected')->count();

            // Profile Statistics  
            $totalProfiles = UserProfile::count();
            $verifiedProfiles = UserProfile::where('is_active_verified', true)->count();
            $maleProfiles = UserProfile::where('gender', 'male')->count();
            $femaleProfiles = UserProfile::where('gender', 'female')->count();

            // Match Statistics
            try {
                $totalMatches = DB::table('matches')->count();
                $acceptedMatches = DB::table('matches')->where('status', 'accepted')->count();
                $pendingMatches = DB::table('matches')->where('status', 'pending')->count();
            } catch (\Exception $e) {
                $totalMatches = 0;
                $acceptedMatches = 0;
                $pendingMatches = 0;
            }

            // Interest Statistics
            try {
                $totalInterests = DB::table('interests_sent')->count();
                $acceptedInterests = DB::table('interests_sent')->where('status', 'accepted')->count();
                $pendingInterests = DB::table('interests_sent')->where('status', 'pending')->count();
            } catch (\Exception $e) {
                $totalInterests = 0;
                $acceptedInterests = 0;
                $pendingInterests = 0;
            }

            // Report Statistics
            try {
                $totalReports = DB::table('reports')->count();
                $pendingReports = DB::table('reports')->where('status', 'pending')->count();
                $resolvedReports = DB::table('reports')->where('status', 'resolved')->count();
            } catch (\Exception $e) {
                $totalReports = 0;
                $pendingReports = 0;
                $resolvedReports = 0;
            }

            // Success Story Statistics
            try {
                $totalStories = DB::table('success_stories')->count();
                $approvedStories = DB::table('success_stories')->where('is_approved', true)->count();
                $pendingStories = DB::table('success_stories')->where('is_approved', false)->count();
            } catch (\Exception $e) {
                $totalStories = 0;
                $approvedStories = 0;
                $pendingStories = 0;
            }

            // Payment Statistics
            try {
                $totalPayments = DB::table('payments')->count();
                $totalRevenue = DB::table('payments')->where('status', 'completed')->sum('amount') ?? 0;
                $revenueThisMonth = DB::table('payments')
                    ->where('status', 'completed')
                    ->whereYear('created_at', now()->year)
                    ->whereMonth('created_at', now()->month)
                    ->sum('amount') ?? 0;

                // Revenue over last 12 months
                $revenueGrowth = [];
                for ($i = 11; $i >= 0; $i--) {
                    $month = now()->subMonths($i);
                    $amount = DB::table('payments')
                        ->where('status', 'completed')
                        ->whereYear('created_at', $month->year)
                        ->whereMonth('created_at', $month->month)
                        ->sum('amount') ?? 0;
                    $revenueGrowth[] = [
                        'month' => $month->format('M Y'),
                        'amount' => (float) $amount
                    ];
                }
            } catch (\Exception $e) {
                $totalPayments = 0;
                $totalRevenue = 0;
                $revenueThisMonth = 0;
                $revenueGrowth = [];
                for ($i = 11; $i >= 0; $i--) {
                    $month = now()->subMonths($i);
                    $revenueGrowth[] = [
                        'month' => $month->format('M Y'),
                        'amount' => 0
                    ];
                }
            }

            // Gender distribution
            $genderDistribution = [
                ['gender' => 'Male', 'count' => $maleProfiles],
                ['gender' => 'Female', 'count' => $femaleProfiles],
            ];

            // Verification status distribution
            $verificationDistribution = [
                ['status' => 'Pending', 'count' => $pendingVerifications],
                ['status' => 'Approved', 'count' => $approvedVerifications],
                ['status' => 'Rejected', 'count' => $rejectedVerifications],
            ];

            // Match status distribution
            $matchDistribution = [
                ['status' => 'Accepted', 'count' => $acceptedMatches],
                ['status' => 'Pending', 'count' => $pendingMatches],
            ];

            return response()->json([
                'users' => [
                    'total' => $totalUsers,
                    'active' => $activeUsers,
                    'blocked' => $blockedUsers,
                    'growth' => $userGrowth,
                ],
                'verifications' => [
                    'pending' => $pendingVerifications,
                    'approved' => $approvedVerifications,
                    'rejected' => $rejectedVerifications,
                    'distribution' => $verificationDistribution,
                ],
                'profiles' => [
                    'total' => $totalProfiles,
                    'verified' => $verifiedProfiles,
                    'male' => $maleProfiles,
                    'female' => $femaleProfiles,
                    'genderDistribution' => $genderDistribution,
                ],
                'matches' => [
                    'total' => $totalMatches,
                    'accepted' => $acceptedMatches,
                    'pending' => $pendingMatches,
                    'distribution' => $matchDistribution,
                ],
                'interests' => [
                    'total' => $totalInterests,
                    'accepted' => $acceptedInterests,
                    'pending' => $pendingInterests,
                ],
                'reports' => [
                    'total' => $totalReports,
                    'pending' => $pendingReports,
                    'resolved' => $resolvedReports,
                ],
                'successStories' => [
                    'total' => $totalStories,
                    'approved' => $approvedStories,
                    'pending' => $pendingStories,
                ],
                'payments' => [
                    'total' => $totalPayments,
                    'totalRevenue' => (float) $totalRevenue,
                    'revenueThisMonth' => (float) $revenueThisMonth,
                    'revenueGrowth' => $revenueGrowth,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch dashboard statistics',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // ==========================================
    // Education Management
    // ==========================================

    public function getEducations(Request $request)
    {
        $query = \App\Models\Education::orderBy('created_at', 'desc');
        if ($request->has('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }
        return response()->json($query->paginate(15));
    }

    public function storeEducation(Request $request)
    {
        $request->validate(['name' => 'required|string|unique:education,name']);

        $education = \App\Models\Education::create([
            'name' => $request->name,
            'is_active' => true,
            'order_number' => 0,
            'popularity_count' => 0
        ]);

        return response()->json(['message' => 'Education added', 'data' => $education]);
    }

    public function updateEducation(Request $request, $id)
    {
        $request->validate(['name' => 'required|string|unique:education,name,' . $id]);

        $education = \App\Models\Education::findOrFail($id);
        $education->update(['name' => $request->name]);

        return response()->json(['message' => 'Education updated', 'data' => $education]);
    }

    public function deleteEducation($id)
    {
        $education = \App\Models\Education::findOrFail($id);
        $education->delete();
        return response()->json(['message' => 'Education deleted']);
    }

    // ==========================================
    // Occupation Management
    // ==========================================

    public function getOccupations(Request $request)
    {
        $query = \App\Models\Occupation::orderBy('created_at', 'desc');
        if ($request->has('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }
        return response()->json($query->paginate(15));
    }

    public function storeOccupation(Request $request)
    {
        $request->validate(['name' => 'required|string|unique:occupations,name']);

        $occupation = \App\Models\Occupation::create([
            'name' => $request->name,
            'is_active' => true,
            'order_number' => 0,
            'popularity_count' => 0
        ]);

        return response()->json(['message' => 'Occupation added', 'data' => $occupation]);
    }

    public function updateOccupation(Request $request, $id)
    {
        $request->validate(['name' => 'required|string|unique:occupations,name,' . $id]);

        $occupation = \App\Models\Occupation::findOrFail($id);
        $occupation->update(['name' => $request->name]);

        return response()->json(['message' => 'Occupation updated', 'data' => $occupation]);
    }

    public function deleteOccupation($id)
    {
        $occupation = \App\Models\Occupation::findOrFail($id);
        $occupation->delete();
        return response()->json(['message' => 'Occupation deleted']);
    }
}
