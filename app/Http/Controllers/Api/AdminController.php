<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\UserVerification;
use App\Models\UserProfile;
use App\Models\User;
use App\Models\FamilyDetail;
use App\Models\Preference;
use App\Models\Education;
use App\Models\Occupation;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Models\Notification;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    /**
     * Get pending verifications
     */
    public function getVerifications(Request $request)
    {
        $status = $request->get('status', 'pending');
        
        $query = UserVerification::with(['user.userProfile', 'user.profilePhotos'])
            ->where('status', $status);

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('id_proof_number', 'like', "%{$search}%")
                    ->orWhere('id_proof_type', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($qu) use ($search) {
                        $qu->where('email', 'like', "%{$search}%")
                            ->orWhere('matrimony_id', 'like', "%{$search}%")
                            ->orWhereHas('userProfile', function ($qp) use ($search) {
                                $qp->where('first_name', 'like', "%{$search}%")
                                    ->orWhere('last_name', 'like', "%{$search}%");
                            });
                    });
            });
        }

        $verifications = $query->orderBy('created_at', 'desc')
            ->paginate(15);

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

        // Create notification for the user
        Notification::create([
            'user_id' => $verification->user_id,
            'sender_id' => $request->user()->id,
            'type' => 'verification',
            'title' => 'ID Verification Approved',
            'message' => 'Your ID verification has been approved. Your profile is now verified.',
            'is_read' => false,
        ]);

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

        // Create notification for the user
        Notification::create([
            'user_id' => $verification->user_id,
            'sender_id' => $request->user()->id,
            'type' => 'verification',
            'title' => 'ID Verification Rejected',
            'message' => 'Your ID verification was rejected. Reason: ' . $request->reason,
            'is_read' => false,
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
        $query = User::withoutGlobalScope('active')->with([
            'userProfile.religionModel',
            'userProfile.casteModel',
            'userProfile.subCasteModel',
            'userProfile.educationModel',
            'userProfile.occupationModel'
        ]);

        if ($request->has('role')) {
            if ($request->role === 'trashed') {
                $query->onlyTrashed();
            } else if ($request->role !== 'all') {
                $query->where('role', $request->role);
            }
        }

        if ($request->has('email_verified') && $request->email_verified !== '') {
            $query->where('email_verified', (bool) $request->email_verified);
        }

        if ($request->has('phone_verified') && $request->phone_verified !== '') {
            $query->where('phone_verified', (bool) $request->phone_verified);
        }

        if ($request->has('search') && $request->search !== '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                    ->orWhere('matrimony_id', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhereHas('userProfile', function ($q) use ($search) {
                        $q->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhereRaw("CONCAT(first_name, ' ', last_name) like ?", ["%{$search}%"]);
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
        $user = User::withoutGlobalScope('active')->findOrFail($id);
        $user->status = $user->status === 'active' ? 'blocked' : 'active';
        $user->save();

        return response()->json([
            'message' => 'User status updated successfully',
            'user' => $user
        ]);
    }

    /**
     * Create a new user
     */
    public function createUser(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:users',
            'phone' => 'required',
            'password' => 'required|min:6',
            'role' => 'required|in:user,admin,mediator',
            'status' => 'required|in:active,blocked',
            'first_name' => 'required',
            'last_name' => 'nullable',
        ]);

        $user = User::create([
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => \Illuminate\Support\Facades\Hash::make($request->password),
            'role' => $request->role,
            'status' => $request->status,
            'email_verified' => $request->boolean('email_verified', true),
            'phone_verified' => $request->boolean('phone_verified', true),
        ]);

        UserProfile::create([
            'user_id' => $user->id,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'gender' => $request->input('gender', 'male'),
            // Dummy default values if needed, otherwise rely on nulls
        ]);

        return response()->json([
            'message' => 'User created successfully',
            'user' => $user->load('userProfile')
        ], 201);
    }

    /**
     * Update an existing user
     */
    public function updateUser(Request $request, $id)
    {
        $user = User::withoutGlobalScope('active')->findOrFail($id);

        $request->validate([
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'required',
            'role' => 'required|in:user,admin,mediator',
            'status' => 'required|in:active,blocked',
            'first_name' => 'required',
        ]);

        $user->update([
            'email' => $request->email,
            'phone' => $request->phone,
            'role' => $request->role,
            'status' => $request->status,
            'email_verified' => $request->boolean('email_verified', $user->email_verified),
            'phone_verified' => $request->boolean('phone_verified', $user->phone_verified),
        ]);

        if ($request->filled('password')) {
            $user->update(['password' => \Illuminate\Support\Facades\Hash::make($request->password)]);
        }

        $profile = UserProfile::firstOrCreate(['user_id' => $user->id]);
        $profile->update([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
        ]);

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user->load('userProfile')
        ]);
    }

    /**
     * Soft delete a user
     */
    public function deleteUser($id)
    {
        $user = User::withoutGlobalScope('active')->withTrashed()->findOrFail($id);
        
        if ($user->trashed()) {
            $user->forceDelete();
            $message = 'User completely deleted';
        } else {
            $user->delete();
            $message = 'User trashed successfully';
        }

        return response()->json([
            'message' => $message
        ]);
    }

    /**
     * Restore a soft-deleted user
     */
    public function restoreUser($id)
    {
        $user = User::withoutGlobalScope('active')->withTrashed()->findOrFail($id);
        
        if ($user->trashed()) {
            $user->restore();
            return response()->json([
                'message' => 'User safely restored'
            ]);
        }

        return response()->json([
            'message' => 'User is not currently soft-deleted',
        ], 400);
    }

    /**
     * Get all user profiles
     */
    public function getUserProfiles(Request $request)
    {
        $query = UserProfile::with([
            'user.verification',
            'religionModel',
            'casteModel',
            'subCasteModel',
            'educationModel',
            'occupationModel'
        ])
            ->join('users', 'user_profiles.user_id', '=', 'users.id')
            ->where('users.role', '!=', 'admin');

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('user_profiles.first_name', 'like', "%{$search}%")
                    ->orWhere('user_profiles.last_name', 'like', "%{$search}%")
                    ->orWhereHas('religionModel', function ($sub) use ($search) {
                        $sub->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('educationModel', function ($sub) use ($search) {
                        $sub->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('occupationModel', function ($sub) use ($search) {
                        $sub->where('name', 'like', "%{$search}%");
                    })
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
        $reports = \App\Models\UserReport::with(['reporter.userProfile', 'reported.userProfile'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        return response()->json($reports);
    }

    /**
     * Resolve report
     */
    public function resolveReport(Request $request, $id)
    {
        $report = \App\Models\UserReport::findOrFail($id);
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
        $payments = \App\Models\Payment::with([
            'user.userProfile.religionModel',
            'user.userProfile.casteModel',
            'user.userProfile.subCasteModel',
            'user.userProfile.educationModel',
            'user.userProfile.occupationModel'
        ])
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        return response()->json($payments);
    }

    /**
     * Get all family details
     */
    public function getFamilyDetails(Request $request)
    {
        $query = FamilyDetail::with([
            'user.userProfile.religionModel',
            'user.userProfile.casteModel',
            'user.userProfile.subCasteModel',
            'user.userProfile.educationModel',
            'user.userProfile.occupationModel'
        ])
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
     * Get all preferences
     */
    public function getPreferences(Request $request)
    {
        $query = Preference::with([
            'user.userProfile.religionModel',
            'user.userProfile.casteModel',
            'user.userProfile.subCasteModel',
            'user.userProfile.educationModel',
            'user.userProfile.occupationModel'
        ])
            ->join('users', 'preferences.user_id', '=', 'users.id')
            ->where('users.role', '!=', 'admin');

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('marital_status', 'like', "%{$search}%")
                    ->orWhere('min_age', 'like', "%{$search}%")
                    ->orWhere('max_age', 'like', "%{$search}%")
                    ->orWhereHas('religion', function ($sub) use ($search) {
                        $sub->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('user.userProfile', function ($subQuery) use ($search) {
                        $subQuery->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    });
            });
        }

        $preferences = $query->select('preferences.*', 'users.id as user_id', 'users.matrimony_id')
            ->orderBy('preferences.updated_at', 'desc')
            ->paginate(15);

        return response()->json($preferences);
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
                $totalReports = DB::table('user_reports')->count();
                $pendingReports = DB::table('user_reports')->where('status', 'pending')->count();
                $resolvedReports = DB::table('user_reports')->where('status', 'resolved')->count();
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
        $query = \App\Models\Education::orderBy('order_number', 'asc')
            ->orderBy('created_at', 'desc');
        if ($request->has('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }
        return response()->json($query->paginate(15));
    }

    public function storeEducation(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:education,name',
            'is_active' => 'boolean',
            'order_number' => 'integer',
            'popularity_count' => 'integer'
        ]);

        $education = \App\Models\Education::create($validated);

        return response()->json(['message' => 'Education added', 'data' => $education]);
    }

    public function updateEducation(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:education,name,' . $id,
            'is_active' => 'boolean',
            'order_number' => 'integer',
            'popularity_count' => 'integer'
        ]);

        $education = \App\Models\Education::findOrFail($id);
        $education->update($validated);

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
        $query = \App\Models\Occupation::orderBy('order_number', 'asc')
            ->orderBy('created_at', 'desc');
        if ($request->has('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }
        return response()->json($query->paginate(15));
    }

    public function storeOccupation(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:occupations,name',
            'is_active' => 'boolean',
            'order_number' => 'integer',
            'popularity_count' => 'integer'
        ]);

        $occupation = \App\Models\Occupation::create($validated);

        return response()->json(['message' => 'Occupation added', 'data' => $occupation]);
    }

    public function updateOccupation(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:occupations,name,' . $id,
            'is_active' => 'boolean',
            'order_number' => 'integer',
            'popularity_count' => 'integer'
        ]);

        $occupation = \App\Models\Occupation::findOrFail($id);
        $occupation->update($validated);

        return response()->json(['message' => 'Occupation updated', 'data' => $occupation]);
    }

    public function deleteOccupation($id)
    {
        $occupation = \App\Models\Occupation::findOrFail($id);
        $occupation->delete();
        return response()->json(['message' => 'Occupation deleted']);
    }

    // ==========================================
    // Wallet Transaction Management
    // ==========================================

    /**
     * Get wallet statistics
     */
    public function getWalletStats()
    {
        try {
            $totalBalance = Wallet::sum('balance');
            $totalRecharge = Transaction::where('type', 'wallet_recharge')
                ->where('status', 'success')
                ->sum('amount');
            $totalSpent = Transaction::where('type', 'contact_unlock')
                ->where('status', 'success')
                ->sum('amount');
            $totalTransactions = Transaction::count();

            return response()->json([
                'totalBalance' => (float) $totalBalance,
                'totalRecharge' => (float) $totalRecharge,
                'totalSpent' => (float) $totalSpent,
                'totalTransactions' => $totalTransactions,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch wallet statistics',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get wallet transactions
     */
    public function getWalletTransactions(Request $request)
    {
        try {
            $query = Transaction::with([
                'user.userProfile.religionModel',
                'user.userProfile.casteModel',
                'user.userProfile.subCasteModel',
                'user.userProfile.educationModel',
                'user.userProfile.occupationModel'
            ])
                ->join('users', 'transactions.user_id', '=', 'users.id')
                ->where('users.role', '!=', 'admin');

            // Search functionality
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('transactions.type', 'like', "%{$search}%")
                        ->orWhere('transactions.status', 'like', "%{$search}%")
                        ->orWhere('transactions.amount', 'like', "%{$search}%")
                        ->orWhere('transactions.description', 'like', "%{$search}%")
                        ->orWhereHas('user.userProfile', function ($subQuery) use ($search) {
                            $subQuery->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('user', function ($subQuery) use ($search) {
                            $subQuery->where('email', 'like', "%{$search}%")
                                ->orWhere('matrimony_id', 'like', "%{$search}%");
                        });
                });
            }

            // Filter functionality
            if ($request->has('filter') && $request->filter !== 'all') {
                $filter = $request->filter;
                if (in_array($filter, ['wallet_recharge', 'contact_unlock'])) {
                    $query->where('transactions.type', $filter);
                } elseif (in_array($filter, ['success', 'pending', 'failed'])) {
                    $query->where('transactions.status', $filter);
                }
            }

            $transactions = $query->select('transactions.*')
                ->orderBy('transactions.created_at', 'desc')
                ->paginate(20);

            return response()->json($transactions);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch wallet transactions',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // ==========================================
    // Interest & Hobby Management
    // ==========================================

    public function getInterests(Request $request)
    {
        $query = \App\Models\InterestHobby::orderBy('trending_number', 'asc')
            ->orderBy('interest_name', 'asc');

        if ($request->has('search')) {
            $query->where('interest_name', 'like', "%{$request->search}%")
                ->orWhere('interest_type', 'like', "%{$request->search}%");
        }

        if ($request->has('type')) {
            $query->where('interest_type', $request->type);
        }

        return response()->json($query->paginate(20));
    }

    public function storeInterest(Request $request)
    {
        $validated = $request->validate([
            'interest_name' => 'required|string|max:100|unique:interests_hobbies,interest_name',
            'interest_type' => 'required|string|max:50',
            'trending_number' => 'integer',
            'is_active' => 'boolean'
        ]);

        $interest = \App\Models\InterestHobby::create($validated);

        return response()->json(['message' => 'Interest added', 'data' => $interest]);
    }

    public function updateInterest(Request $request, $id)
    {
        $validated = $request->validate([
            'interest_name' => 'required|string|max:100|unique:interests_hobbies,interest_name,' . $id,
            'interest_type' => 'required|string|max:50',
            'trending_number' => 'integer',
            'is_active' => 'boolean'
        ]);

        $interest = \App\Models\InterestHobby::findOrFail($id);
        $interest->update($validated);

        return response()->json(['message' => 'Interest updated', 'data' => $interest]);
    }

    public function deleteInterest($id)
    {
        $interest = \App\Models\InterestHobby::findOrFail($id);
        $interest->delete();
        return response()->json(['message' => 'Interest deleted']);
    }

    // ==========================================
    // Audit & Security Logs
    // ==========================================

    public function getLoginHistories(Request $request)
    {
        try {
            $query = \App\Models\UserLoginHistory::with(['user.userProfile']);

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('ip_address', 'like', "%{$search}%")
                        ->orWhere('user_agent', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%")
                        ->orWhereHas('user', function($qu) use ($search) {
                            $qu->where('email', 'like', "%{$search}%")
                                ->orWhere('matrimony_id', 'like', "%{$search}%")
                                ->orWhereHas('userProfile', function($qp) use ($search) {
                                    $qp->where('first_name', 'like', "%{$search}%")
                                        ->orWhere('last_name', 'like', "%{$search}%");
                                });
                        });
                });
            }

            return response()->json($query->orderBy('login_at', 'desc')->paginate(20));
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch login history', 'message' => $e->getMessage()], 500);
        }
    }

    public function getActivityLogs(Request $request)
    {
        try {
            $query = \App\Models\ActivityLog::with(['user.userProfile']);

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('action', 'like', "%{$search}%")
                        ->orWhere('ip_address', 'like', "%{$search}%")
                        ->orWhere('details', 'like', "%{$search}%")
                        ->orWhereHas('user', function($qu) use ($search) {
                            $qu->where('email', 'like', "%{$search}%")
                                ->orWhere('matrimony_id', 'like', "%{$search}%")
                                ->orWhereHas('userProfile', function($qp) use ($search) {
                                    $qp->where('first_name', 'like', "%{$search}%")
                                        ->orWhere('last_name', 'like', "%{$search}%");
                                });
                        });
                });
            }

            return response()->json($query->orderBy('created_at', 'desc')->paginate(20));
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch activity logs', 'message' => $e->getMessage()], 500);
        }
    }

    public function getContactUnlocks(Request $request)
    {
        try {
            $query = \App\Models\ContactUnlock::with(['user.userProfile', 'unlockedUser.userProfile']);

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('payment_method', 'like', "%{$search}%")
                        ->orWhereHas('user', function($qu) use ($search) {
                            $qu->where('email', 'like', "%{$search}%")
                                ->orWhere('matrimony_id', 'like', "%{$search}%")
                                ->orWhereHas('userProfile', function($qp) use ($search) {
                                    $qp->where('first_name', 'like', "%{$search}%")
                                        ->orWhere('last_name', 'like', "%{$search}%");
                                });
                        })
                        ->orWhereHas('unlockedUser', function($qu) use ($search) {
                            $qu->where('email', 'like', "%{$search}%")
                                ->orWhere('matrimony_id', 'like', "%{$search}%")
                                ->orWhereHas('userProfile', function($qp) use ($search) {
                                    $qp->where('first_name', 'like', "%{$search}%")
                                        ->orWhere('last_name', 'like', "%{$search}%");
                                });
                        });
                });
            }

            return response()->json($query->orderBy('created_at', 'desc')->paginate(20));
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch contact unlocks', 'message' => $e->getMessage()], 500);
        }
    }
}
