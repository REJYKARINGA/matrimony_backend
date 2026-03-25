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
use App\Models\Religion;
use App\Models\Caste;
use App\Models\SubCaste;
use App\Models\UserReport;
use App\Models\ProfilePhoto;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

        if ($request->has('status') && $request->status !== 'all' && $request->status !== '') {
            $query->where('status', $request->status);
        }

        if ($request->has('gender') && $request->gender !== 'all' && $request->gender !== '') {
            $query->whereHas('userProfile', function ($q) use ($request) {
                $q->where('gender', $request->gender);
            });
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

    public function getUsersWithoutProfile(Request $request)
    {
        $users = User::withoutGlobalScope('active')
            ->whereDoesntHave('userProfile')
            ->select('id', 'matrimony_id', 'email', 'phone', 'role', 'status', 'email_verified', 'phone_verified')
            ->get();
        return response()->json($users);
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

        // Handle trashed tab
        if ($request->trashed == '1') {
            $query->onlyTrashed();
        }

        // Search by name (first+last concat), email, matrimony_id
        if ($request->has('search') && $request->search !== '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('user_profiles.first_name', 'like', "%{$search}%")
                    ->orWhere('user_profiles.last_name', 'like', "%{$search}%")
                    ->orWhereRaw("CONCAT(user_profiles.first_name, ' ', user_profiles.last_name) like ?", ["%{$search}%"])
                    ->orWhere('users.email', 'like', "%{$search}%")
                    ->orWhere('users.matrimony_id', 'like', "%{$search}%");
            });
        }

        // Gender filter
        if ($request->has('gender') && $request->gender !== 'all' && $request->gender !== '') {
            $query->where('user_profiles.gender', $request->gender);
        }

        // Religion filter
        if ($request->has('religion_id') && $request->religion_id !== 'all' && $request->religion_id !== '') {
            $query->where('user_profiles.religion_id', $request->religion_id);
        }

        // Education filter
        if ($request->has('education_id') && $request->education_id !== 'all' && $request->education_id !== '') {
            $query->where('user_profiles.education_id', $request->education_id);
        }

        // Occupation filter
        if ($request->has('occupation_id') && $request->occupation_id !== 'all' && $request->occupation_id !== '') {
            $query->where('user_profiles.occupation_id', $request->occupation_id);
        }

        // Profile active status filter
        if ($request->has('is_active_verified') && $request->is_active_verified !== 'all' && $request->is_active_verified !== '') {
            $query->where('user_profiles.is_active_verified', (bool) $request->is_active_verified);
        }

        // Verification status filter (from user.verification)
        if ($request->has('verification_status') && $request->verification_status !== 'all' && $request->verification_status !== '') {
            if ($request->verification_status === 'not_submitted') {
                $query->whereDoesntHave('user.verification');
            } else {
                $query->whereHas('user.verification', function ($q) use ($request) {
                    $q->where('status', $request->verification_status);
                });
            }
        }

        // Age range filter (calculated from date_of_birth)
        if ($request->has('age_min') && $request->age_min !== '') {
            $maxDob = now()->subYears((int) $request->age_min)->toDateString();
            $query->where('user_profiles.date_of_birth', '<=', $maxDob);
        }
        if ($request->has('age_max') && $request->age_max !== '') {
            $minDob = now()->subYears((int) $request->age_max + 1)->addDay()->toDateString();
            $query->where('user_profiles.date_of_birth', '>=', $minDob);
        }

        $profiles = $query->select('user_profiles.*')
            ->orderBy('user_profiles.created_at', 'desc')
            ->paginate(15);

        return response()->json($profiles);
    }

    /**
     * Store a new user profile
     */
    public function storeProfile(Request $request)
    {
        $validated = $request->validate([
            'user_id'            => 'required|exists:users,id',
            'first_name'         => 'required|string|max:100',
            'last_name'          => 'required|string|max:100',
            'date_of_birth'      => 'nullable|date',
            'gender'             => 'nullable|in:male,female',
            'height'             => 'nullable|integer',
            'weight'             => 'nullable|integer',
            'marital_status'     => 'nullable|string',
            'mother_tongue'      => 'nullable|string|max:100',
            'drug_addiction'     => 'nullable|boolean',
            'smoke'              => 'nullable|boolean',
            'alcohol'            => 'nullable|boolean',
            'religion_id'        => 'nullable|integer',
            'caste_id'           => 'nullable|integer',
            'sub_caste_id'       => 'nullable|integer',
            'education_id'       => 'nullable|integer',
            'occupation_id'      => 'nullable|integer',
            'annual_income'      => 'nullable|numeric',
            'city'               => 'nullable|string|max:100',
            'district'           => 'nullable|string|max:100',
            'county'             => 'nullable|string|max:100',
            'state'              => 'nullable|string|max:100',
            'country'            => 'nullable|string|max:100',
            'present_city'       => 'nullable|string|max:100',
            'present_country'    => 'nullable|string|max:100',
            'postal_code'        => 'nullable|string|max:20',
            'bio'                => 'nullable|string',
            'hide_photos'        => 'nullable|boolean',
            'is_active_verified' => 'nullable|boolean',
            'profile_picture'    => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($request->hasFile('profile_picture')) {
            $path = $request->file('profile_picture')->store('profiles', 'public');
            $validated['profile_picture'] = $path;
        }

        $profile = UserProfile::create($validated);
        $profile->load(['user.verification', 'religionModel', 'casteModel', 'subCasteModel', 'educationModel', 'occupationModel']);

        return response()->json(['message' => 'Profile created successfully', 'profile' => $profile], 201);
    }

    /**
     * Update an existing user profile
     */
    public function updateProfile(Request $request, $id)
    {
        try {
            $profile = UserProfile::withTrashed()->findOrFail($id);

            $validated = $request->validate([
                'first_name'         => 'sometimes|required|string|max:100',
                'last_name'          => 'sometimes|required|string|max:100',
                'date_of_birth'      => 'nullable|date',
                'gender'             => 'nullable|in:male,female',
                'height'             => 'nullable|integer',
                'weight'             => 'nullable|integer',
                'marital_status'     => 'nullable|string',
                'mother_tongue'      => 'nullable|string|max:100',
                'drug_addiction'     => 'nullable|boolean',
                'smoke'              => 'nullable|boolean',
                'alcohol'            => 'nullable|boolean',
                'religion_id'        => 'nullable|integer',
                'caste_id'           => 'nullable|integer',
                'sub_caste_id'       => 'nullable|integer',
                'education_id'       => 'nullable|integer',
                'occupation_id'      => 'nullable|integer',
                'annual_income'      => 'nullable|numeric',
                'city'               => 'nullable|string|max:100',
                'district'           => 'nullable|string|max:100',
                'county'             => 'nullable|string|max:100',
                'state'              => 'nullable|string|max:100',
                'country'            => 'nullable|string|max:100',
                'present_city'       => 'nullable|string|max:100',
                'present_country'    => 'nullable|string|max:100',
                'postal_code'        => 'nullable|string|max:20',
                'bio'                => 'nullable|string',
                'hide_photos'        => 'nullable|boolean',
                'is_active_verified' => 'nullable|boolean',
                'profile_picture'    => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            if ($request->hasFile('profile_picture')) {
                $path = $request->file('profile_picture')->store('profiles', 'public');
                $validated['profile_picture'] = $path;
                
                // Delete old picture if it exists and is not a remote URL
                if ($profile->profile_picture && strpos($profile->profile_picture, 'http') !== 0) {
                    try {
                        \Illuminate\Support\Facades\Storage::disk('public')->delete($profile->profile_picture);
                    } catch (\Exception $e) {
                        // Ignore missing files or permission errors during delete
                    }
                }
            }

            $profile->update($validated);
            $profile->load(['user.verification', 'religionModel', 'casteModel', 'subCasteModel', 'educationModel', 'occupationModel']);

            return response()->json(['message' => 'Profile updated successfully', 'profile' => $profile]);
        } catch (\Exception $e) {
            Log::error("Failed to update profile ID $id: " . $e->getMessage());
            return response()->json(['error' => 'Failed to update profile', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Soft delete a profile (or force delete if already trashed)
     */
    public function deleteProfile($id)
    {
        $profile = UserProfile::withTrashed()->findOrFail($id);

        if ($profile->trashed()) {
            $profile->forceDelete();
            return response()->json(['message' => 'Profile permanently deleted']);
        }

        $profile->delete();
        return response()->json(['message' => 'Profile trashed successfully']);
    }

    /**
     * Restore a trashed profile
     */
    public function restoreProfile($id)
    {
        $profile = UserProfile::withTrashed()->findOrFail($id);

        if (!$profile->trashed()) {
            return response()->json(['message' => 'Profile is not trashed'], 400);
        }

        $profile->restore();
        return response()->json(['message' => 'Profile restored successfully']);
    }

    /**
     * Get all users who have reported or been reported
     */
    public function getReportParticipants()
    {
        $reporterIds = UserReport::distinct()->pluck('reporter_id');
        $reportedIds = UserReport::distinct()->pluck('reported_id');
        
        $reporters = User::whereIn('id', $reporterIds)
            ->with('userProfile')
            ->get(['id', 'matrimony_id']);
            
        $reported = User::whereIn('id', $reportedIds)
            ->with('userProfile')
            ->get(['id', 'matrimony_id']);

        return response()->json([
            'reporters' => $reporters,
            'reported' => $reported
        ]);
    }

    /**
     * Get all reports
     */
    public function getReports(Request $request)
    {
        $query = UserReport::query();

        // Reporter Search (Matrimony ID or Name)
        if ($request->reporter_search) {
            $query->where(function($q) use ($request) {
                $q->whereHas('reporter', function($sq) use ($request) {
                    $sq->where('matrimony_id', 'like', '%' . $request->reporter_search . '%');
                })->orWhereHas('reporter.userProfile', function($sq) use ($request) {
                    $sq->where('first_name', 'like', '%' . $request->reporter_search . '%')
                      ->orWhere('last_name', 'like', '%' . $request->reporter_search . '%');
                });
            });
        }

        // Reported User Search (Matrimony ID or Name)
        if ($request->reported_search) {
            $query->where(function($q) use ($request) {
                $q->whereHas('reported', function($sq) use ($request) {
                    $sq->where('matrimony_id', 'like', '%' . $request->reported_search . '%');
                })->orWhereHas('reported.userProfile', function($sq) use ($request) {
                    $sq->where('first_name', 'like', '%' . $request->reported_search . '%')
                      ->orWhere('last_name', 'like', '%' . $request->reported_search . '%');
                });
            });
        }

        // Status Filter
        if ($request->status && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $reports = $query->with([
            'reporter.userProfile', 
            'reported' => function($q) {
                $q->withCount('receivedUserReports')->with('userProfile');
            }, 
            'reviewer.userProfile'
        ])
        ->orderBy('created_at', 'desc')
        ->paginate(10);

        return response()->json($reports);
    }

    /**
     * Resolve report
     */
    public function resolveReport(Request $request, $id)
    {
        try {
            Log::info('Attempting to resolve report', ['id' => $id, 'user_id' => auth()->id(), 'notes' => $request->resolution_notes]);
            $report = UserReport::findOrFail($id);
            
            // Check if model has the columns
            $report->update([
                'status' => 'resolved',
                'resolution_notes' => $request->resolution_notes,
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
            ]);

            return response()->json(['message' => 'Report resolved', 'report' => $report]);
        } catch (\Exception $e) {
            Log::error('Report Resolution Error: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to resolve report: ' . $e->getMessage()], 500);
        }
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

        // Top 2 Marital Statuses
        $statusStats = Preference::whereNotNull('marital_status')->groupBy('marital_status')->selectRaw('marital_status, count(*) as count')->orderByDesc('count')->limit(2)->get();
        // Top 2 Religions
        $religionStats = Preference::whereNotNull('religion_id')->groupBy('religion_id')->selectRaw('religion_id, count(*) as count')->orderByDesc('count')->limit(2)->get()->map(fn($r) => Religion::find($r->religion_id)?->name)->filter();
        
        $mostCommonReligionId = Preference::whereNotNull('religion_id')->groupBy('religion_id')->selectRaw('religion_id, count(*) as count')->orderByDesc('count')->limit(1)->pluck('religion_id')->first();
        
        // Helper to get top 2 names from JSON ID arrays
        $getTopNames = function($query, $column, $modelClass) {
            $idArrays = $query->whereNotNull($column)->pluck($column);
            $counts = [];
            foreach ($idArrays as $arr) { if (is_array($arr)) { foreach ($arr as $id) { $counts[$id] = ($counts[$id] ?? 0) + 1; } } }
            arsort($counts);
            return collect(array_slice($counts, 0, 2, true))->map(fn($c, $id) => $modelClass::find($id)?->name)->filter()->values()->toArray();
        };

        $topCastes = $getTopNames(Preference::where('religion_id', $mostCommonReligionId), 'caste_ids', Caste::class);
        $topSubCastes = $getTopNames(Preference::where('religion_id', $mostCommonReligionId), 'sub_caste_ids', SubCaste::class);
        $topDrugs = Preference::groupBy('drug_addiction')->selectRaw('drug_addiction, count(*) as count')->orderByDesc('count')->limit(2)->pluck('drug_addiction')->toArray();

        $stats = [
            'status' => $statusStats->pluck('marital_status')->toArray(),
            'religion' => $religionStats->values()->toArray(),
            'caste' => $topCastes,
            'sub_caste' => $topSubCastes,
            'avg_age' => ['min' => round(Preference::avg('min_age')), 'max' => round(Preference::avg('max_age'))],
            'avg_height' => ['min' => round(Preference::avg('min_height')), 'max' => round(Preference::avg('max_height'))],
            'avg_income' => ['min' => round(Preference::avg('min_income')), 'max' => round(Preference::avg('max_income'))],
            'lifestyle' => $topDrugs,
            'total_processed' => Preference::count()
        ];

        return response()->json([
            'data' => $preferences->items(),
            'current_page' => $preferences->currentPage(),
            'last_page' => $preferences->lastPage(),
            'total' => $preferences->total(),
            'stats' => $stats
        ]);
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

            // Religion distribution
            $religionDistribution = DB::table('user_profiles')
                ->join('religions', 'user_profiles.religion_id', '=', 'religions.id')
                ->select('religions.name as religion', DB::raw('count(*) as count'))
                ->groupBy('religions.name')
                ->get();

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

            // Wallet Statistics
            try {
                $totalWalletBalance = DB::table('wallets')->sum('balance') ?? 0;
                $totalWalletTransactions = DB::table('transactions')->count();
            } catch (\Exception $e) {
                $totalWalletBalance = 0;
                $totalWalletTransactions = 0;
            }

            // Data Management Statistics
            try {
                $totalEducations = DB::table('education')->count();
                $totalOccupations = DB::table('occupations')->count();
                $totalInterestsLibrary = DB::table('interests_hobbies')->count();
                $totalReligions = DB::table('religions')->count();
                $totalCastes = DB::table('castes')->count();
            } catch (\Exception $e) {
                $totalEducations = 0; $totalOccupations = 0; $totalInterestsLibrary = 0; $totalReligions = 0; $totalCastes = 0;
            }

            // Engagement Poster Statistics
            try {
                $totalPosters = DB::table('engagement_posters')->count();
                $verifiedPosters = DB::table('engagement_posters')->where('is_verified', true)->count();
            } catch (\Exception $e) {
                $totalPosters = 0; $verifiedPosters = 0;
            }

            // Family & Preference Statistics
            try {
                $totalFamilyDetails = DB::table('family_details')->count();
                $totalPreferences = DB::table('preferences')->count();
            } catch (\Exception $e) {
                $totalFamilyDetails = 0; $totalPreferences = 0;
            }

            // Contact Unlock Statistics
            try {
                $totalContactUnlocks = DB::table('contact_unlocks')->count();
            } catch (\Exception $e) {
                $totalContactUnlocks = 0;
            }

            // Audit Logs Statistics
            try {
                $totalLoginHistories = DB::table('user_login_histories')->count();
                $totalActivityLogs = DB::table('activity_logs')->count();
                $logsToday = DB::table('activity_logs')
                    ->whereDate('created_at', now()->toDateString())
                    ->count();
            } catch (\Exception $e) {
                $totalLoginHistories = 0; $totalActivityLogs = 0; $logsToday = 0;
            }

            // Feature Request / Suggestion Statistics
            try {
                $totalSuggestions    = DB::table('suggestions')->count();
                $pendingSuggestions  = DB::table('suggestions')->where('status', 'pending')->count();
                $inProgressSuggestions = DB::table('suggestions')->where('status', 'in_progress')->count();
                $completedSuggestions  = DB::table('suggestions')->where('status', 'completed')->count();
                $rejectedSuggestions   = DB::table('suggestions')->where('status', 'rejected')->count();
                $recentSuggestions = DB::table('suggestions')
                    ->join('users', 'suggestions.user_id', '=', 'users.id')
                    ->leftJoin('user_profiles', 'users.id', '=', 'user_profiles.user_id')
                    ->where('suggestions.status', 'pending')
                    ->select(
                        'suggestions.id',
                        'suggestions.title',
                        'suggestions.category',
                        'suggestions.status',
                        'suggestions.created_at',
                        'users.matrimony_id',
                        \DB::raw("TRIM(CONCAT(COALESCE(user_profiles.first_name,''), ' ', COALESCE(user_profiles.last_name,''))) as user_name"),
                        'users.email as user_email'
                    )
                    ->orderBy('suggestions.created_at', 'desc')
                    ->limit(5)
                    ->get();
            } catch (\Exception $e) {
                $totalSuggestions = 0; $pendingSuggestions = 0; $inProgressSuggestions = 0;
                $completedSuggestions = 0; $rejectedSuggestions = 0; $recentSuggestions = [];
            }

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
                    'religionDistribution' => $religionDistribution,
                    'familyDetails' => $totalFamilyDetails,
                    'preferences' => $totalPreferences,
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
                    'libraryTotal' => $totalInterestsLibrary,
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
                    'walletBalance' => (float) $totalWalletBalance,
                    'walletTransactions' => $totalWalletTransactions,
                ],
                'content' => [
                    'posters' => $totalPosters,
                    'postersVerified' => $verifiedPosters,
                ],
                'dataManagement' => [
                    'education' => $totalEducations,
                    'occupation' => $totalOccupations,
                    'interests' => $totalInterestsLibrary,
                    'religions' => $totalReligions,
                    'castes' => $totalCastes,
                ],
                'unlocks' => [
                    'total' => $totalContactUnlocks,
                ],
                'audit' => [
                    'loginHistories' => $totalLoginHistories,
                    'activityLogs' => $totalActivityLogs,
                    'logsToday' => $logsToday,
                ],
                'featureRequests' => [
                    'total'       => $totalSuggestions,
                    'pending'     => $pendingSuggestions,
                    'in_progress' => $inProgressSuggestions,
                    'completed'   => $completedSuggestions,
                    'rejected'    => $rejectedSuggestions,
                    'recent'      => $recentSuggestions,
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

    // ==========================================
    // Engagement Posters Management
    // ==========================================

    public function getEngagementPosters(Request $request)
    {
        try {
            $query = \App\Models\EngagementPoster::with(['user.userProfile', 'partner.userProfile']);

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('announcement_title', 'like', "%{$search}%")
                      ->orWhere('announcement_message', 'like', "%{$search}%")
                      ->orWhere('partner_matrimony_id', 'like', "%{$search}%")
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
            return response()->json(['error' => 'Failed to fetch posters', 'message' => $e->getMessage()], 500);
        }
    }

    public function verifyEngagementPoster($id)
    {
        try {
            $poster = \App\Models\EngagementPoster::findOrFail($id);
            $poster->is_verified = true;
            $poster->save();

            // Notify Creator
            \App\Models\Notification::create([
                'user_id' => $poster->user_id,
                'sender_id' => auth()->id(), // Admin's user ID
                'type' => 'engagement_poster_verified',
                'title' => 'Poster Verified',
                'message' => 'Congratulations! Your engagement poster has been verified by the admin and is now visible to everyone.',
                'reference_id' => $poster->id
            ]);

            // Notify Partner if exists
            if ($poster->partner_matrimony_id) {
                $partnerUser = \App\Models\User::where('matrimony_id', $poster->partner_matrimony_id)->first();
                if ($partnerUser) {
                    \App\Models\Notification::create([
                        'user_id' => $partnerUser->id,
                        'sender_id' => auth()->id(),
                        'type' => 'engagement_poster_verified',
                        'title' => 'Poster Verified',
                        'message' => 'Congratulations! The engagement announcement you are part of has been verified by the admin and is now visible to everyone.',
                        'reference_id' => $poster->id
                    ]);
                }
            }

            return response()->json(['message' => 'Poster verified successfully', 'data' => $poster]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to verify poster', 'message' => $e->getMessage()], 500);
        }
    }

    public function deleteEngagementPoster($id)
    {
        try {
            $poster = \App\Models\EngagementPoster::findOrFail($id);
            
            // Delete image if exists
            if ($poster->poster_image) {
                if (str_contains($poster->poster_image, 'res.cloudinary.com')) {
                    try {
                        $urlParts = parse_url($poster->poster_image);
                        $pathParts = explode('/', trim($urlParts['path'], '/'));
                        $publicIdWithExt = end($pathParts);
                        $publicId = pathinfo($publicIdWithExt, PATHINFO_FILENAME);
                        cloudinary()->uploadApi()->destroy('matrimony/engagement_posters/' . $poster->user_id . '/' . $publicId);
                    } catch (\Exception $e) {}
                } else {
                    $oldImagePath = str_replace('/storage/', '', $poster->poster_image);
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($oldImagePath);
                }
            }
            
            $poster->delete();
            return response()->json(['message' => 'Poster deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete poster', 'message' => $e->getMessage()], 500);
        }
    }

    // ==========================================
    // Suggestions Management
    // ==========================================

    public function getSuggestions(Request $request)
    {
        try {
            $query = \App\Models\Suggestion::with(['user.userProfile', 'responder.userProfile']);

            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
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

            if ($request->has('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            $stats = [
                'total'       => \App\Models\Suggestion::count(),
                'pending'     => \App\Models\Suggestion::where('status', 'pending')->count(),
                'in_progress' => \App\Models\Suggestion::where('status', 'in_progress')->count(),
                'completed'   => \App\Models\Suggestion::where('status', 'completed')->count(),
                'rejected'    => \App\Models\Suggestion::where('status', 'rejected')->count(),
            ];

            $suggestions = $query->orderBy('created_at', 'desc')->paginate(15);

            return response()->json([
                'data'         => $suggestions->items(),
                'current_page' => $suggestions->currentPage(),
                'last_page'    => $suggestions->lastPage(),
                'total'        => $suggestions->total(),
                'stats'        => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch suggestions', 'message' => $e->getMessage()], 500);
        }
    }

    public function respondToSuggestion(Request $request, $id)
    {
        try {
            $suggestion = \App\Models\Suggestion::findOrFail($id);

            $validated = $request->validate([
                'status'        => 'required|in:pending,in_progress,completed,rejected',
                'response_text' => 'nullable|string|max:2000',
                'response_photo'=> 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            ]);

            $suggestion->status = $validated['status'];
            if ($request->has('response_text')) {
                $suggestion->response_text = $validated['response_text'];
            }

            if ($request->hasFile('response_photo')) {
                $photo = $request->file('response_photo');
                if (env('CLOUDINARY_URL')) {
                    $uploadResult = cloudinary()->uploadApi()->upload($photo->getRealPath(), [
                        'folder' => 'matrimony/suggestions/responses'
                    ]);
                    $suggestion->response_photo = $uploadResult['secure_url'];
                } else {
                    $path = $photo->store('suggestions/responses', 'public');
                    $suggestion->response_photo = '/storage/' . $path;
                }
            }

            $suggestion->responded_by = auth()->id();
            $suggestion->responded_at = now();
            $suggestion->save();

            // Notify the user about the update
            $statusLabels = [
                'pending' => 'Under Review',
                'in_progress' => 'In Development',
                'completed' => 'Completed',
                'rejected' => 'Rejected'
            ];
            
            $statusLabel = $statusLabels[$suggestion->status] ?? $suggestion->status;

            \App\Models\Notification::create([
                'user_id' => $suggestion->user_id,
                'sender_id' => auth()->id(),
                'type' => 'suggestion_update',
                'title' => 'Suggestion Update',
                'message' => "Your feature request '{$suggestion->title}' has been updated to: {$statusLabel}.",
                'reference_id' => $suggestion->id
            ]);

            return response()->json(['message' => 'Suggestion updated successfully', 'data' => $suggestion->load('user.userProfile', 'responder.userProfile')]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update suggestion', 'message' => $e->getMessage()], 500);
        }
    }

    public function deleteSuggestion($id)
    {
        try {
            $suggestion = \App\Models\Suggestion::findOrFail($id);
            $suggestion->delete();
            return response()->json(['message' => 'Suggestion deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete suggestion', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get all profile photos (pending/verified/rejected)
     */
    public function getProfilePhotos(Request $request)
    {
        $status = $request->get('status', 'pending');
        
        // Fetch users who have photos matching the status
        $query = User::with(['userProfile', 'profilePhotos' => function($q) use ($status) {
            if ($status === 'pending') {
                $q->where('is_verified', false)->whereNull('verification_date');
            } elseif ($status === 'verified') {
                $q->where('is_verified', true);
            } elseif ($status === 'rejected') {
                $q->where('is_verified', false)->whereNotNull('verification_date');
            }
            $q->orderBy('upload_date', 'desc');
        }]);

        $query->whereHas('profilePhotos', function($q) use ($status) {
            if ($status === 'pending') {
                $q->where('is_verified', false)->whereNull('verification_date');
            } elseif ($status === 'verified') {
                $q->where('is_verified', true);
            } elseif ($status === 'rejected') {
                $q->where('is_verified', false)->whereNotNull('verification_date');
            }
        });

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($qu) use ($search) {
                $qu->where('email', 'like', "%{$search}%")
                    ->orWhere('matrimony_id', 'like', "%{$search}%")
                    ->orWhereHas('userProfile', function ($qp) use ($search) {
                        $qp->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhereRaw("CONCAT(first_name, ' ', last_name) like ?", ["%{$search}%"]);
                    });
            });
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(12);

        return response()->json($users);
    }

    /**
     * Verify a profile photo
     */
    public function verifyProfilePhoto(Request $request, $id)
    {
        $photo = ProfilePhoto::findOrFail($id);
        
        $photo->update([
            'is_verified' => true,
            'verified_by' => auth()->id(),
            'verification_date' => now()
        ]);

        // Notify user
        \App\Models\Notification::create([
            'user_id' => $photo->user_id,
            'sender_id' => auth()->id(),
            'type' => 'photo_verification',
            'title' => 'Photo Verified',
            'message' => 'One of your uploaded photos has been verified and is now visible to others.',
            'is_read' => false
        ]);

        return response()->json([
            'message' => 'Photo verified successfully',
            'photo' => $photo
        ]);
    }

    /**
     * Reject a profile photo
     */
    public function rejectProfilePhoto(Request $request, $id)
    {
        $photo = ProfilePhoto::findOrFail($id);
        
        $photo->update([
            'is_verified' => false,
            'verified_by' => auth()->id(),
            'verification_date' => now()
        ]);

        // Notify user
        \App\Models\Notification::create([
            'user_id' => $photo->user_id,
            'sender_id' => auth()->id(),
            'type' => 'photo_verification',
            'title' => 'Photo Rejected',
            'message' => 'Your recent photo upload was rejected as it did not meet our community standards.',
            'is_read' => false
        ]);

        return response()->json([
            'message' => 'Photo rejected successfully',
            'photo' => $photo
        ]);
    }
}
