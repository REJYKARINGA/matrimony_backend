<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\FamilyDetail;
use App\Models\Preference;
use App\Models\ProfilePhoto;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    /**
     * Get authenticated user's profile
     */
    public function myProfile(Request $request)
    {
        $user = $request->user();

        // Check if user is soft deleted
        if ($user->trashed()) {
            return response()->json([
                'error' => 'Account has been deleted'
            ], 403);
        }

        $user->load(['userProfile', 'familyDetails', 'preferences', 'profilePhotos']);

        return response()->json([
            'user' => $user
        ]);
    }

    /**
     * Update authenticated user's profile
     */
    public function updateMyProfile(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'date_of_birth' => 'sometimes|date',
            'gender' => 'sometimes|string|in:male,female,other',
            'height' => 'sometimes|integer',
            'weight' => 'sometimes|integer',
            'marital_status' => 'sometimes|string|in:never_married,divorced,widowed',
            'religion' => 'sometimes|string|max:255',
            'caste' => 'sometimes|string|max:255',
            'sub_caste' => 'nullable|string|max:255',
            'mother_tongue' => 'sometimes|string|max:255',
            'profile_picture' => 'sometimes|string|max:255',
            'bio' => 'sometimes|string',
            'education' => 'sometimes|string|max:255',
            'occupation' => 'sometimes|string|max:255',
            'annual_income' => 'sometimes|numeric',
            'city' => 'sometimes|string|max:255',
            'district' => 'sometimes|string|in:Thiruvananthapuram,Kollam,Pathanamthitta,Alappuzha,Kottayam,Idukki,Ernakulam,Thrissur,Palakkad,Malappuram,Kozhikode,Wayanad,Kannur,Kasaragod',
            'state' => 'sometimes|string|max:255',
            'country' => 'sometimes|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }

        // Age validation: Female must be at least 18 years old, Male must be at least 21 years old
        if ($request->filled('date_of_birth') && $request->filled('gender')) {
            $dateOfBirth = Carbon::parse($request->date_of_birth);
            $age = $dateOfBirth->age;

            if ($request->gender === 'female' && $age < 18) {
                return response()->json([
                    'error' => 'Age restriction',
                    'messages' => ['date_of_birth' => ['Female users must be at least 18 years old to register']]
                ], 422);
            } elseif ($request->gender === 'male' && $age < 21) {
                return response()->json([
                    'error' => 'Age restriction',
                    'messages' => ['date_of_birth' => ['Male users must be at least 21 years old to register']]
                ], 422);
            }
        }

        // Check if profile exists
        $profileExists = UserProfile::where('user_id', $user->id)->exists();

        // Update or create user profile
        $userProfile = UserProfile::updateOrCreate(
            ['user_id' => $user->id],
            array_merge(
                $request->only([
                    'first_name',
                    'last_name',
                    'date_of_birth',
                    'gender',
                    'height',
                    'weight',
                    'marital_status',
                    'religion',
                    'caste',
                    'sub_caste',
                    'mother_tongue',
                    'profile_picture',
                    'bio',
                    'education',
                    'occupation',
                    'annual_income',
                    'city',
                    'district',
                    'state',
                    'country'
                ]),
                ['user_id' => $user->id]
            )
        );

        // If profile existed (update), set is_active_verified to false
        if ($profileExists) {
            $userProfile->update(['is_active_verified' => false]);
        }

        return response()->json([
            'message' => 'Profile updated successfully',
            'profile' => $userProfile
        ]);
    }

    /**
     * Update authenticated user's family details
     */
    public function updateFamilyDetails(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'father_name' => 'sometimes|string|max:255',
            'father_occupation' => 'sometimes|string|max:255',
            'mother_name' => 'sometimes|string|max:255',
            'mother_occupation' => 'sometimes|string|max:255',
            'siblings' => 'sometimes|integer|min:0',
            'family_type' => 'sometimes|string|in:joint,nuclear',
            'family_status' => 'sometimes|string|in:middle_class,upper_middle_class,rich',
            'family_location' => 'sometimes|string|max:255',
            'elder_sister' => 'sometimes|integer|min:0',
            'elder_brother' => 'sometimes|integer|min:0',
            'younger_sister' => 'sometimes|integer|min:0',
            'younger_brother' => 'sometimes|integer|min:0',
            'twin_type' => 'sometimes|string|in:identical,fraternal',
            'father_alive' => 'sometimes|boolean',
            'mother_alive' => 'sometimes|boolean',
            'guardian' => 'sometimes|string|max:255',
            'show' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }

        // Update or create family details
        $familyDetails = FamilyDetail::updateOrCreate(
            ['user_id' => $user->id],
            $request->only([
                'father_name',
                'father_occupation',
                'mother_name',
                'mother_occupation',
                'siblings',
                'family_type',
                'family_status',
                'family_location',
                'elder_sister',
                'elder_brother',
                'younger_sister',
                'younger_brother',
                'twin_type',
                'father_alive',
                'mother_alive',
                'guardian',
                'show'
            ])
        );

        return response()->json([
            'message' => 'Family details updated successfully',
            'family_details' => $familyDetails
        ]);
    }

    /**
     * Update authenticated user's preferences
     */
    public function updatePreferences(Request $request)
    {
        $user = $request->user();

        $data = $request->all();

        // Handle preferred_locations if sent as JSON string
        if (isset($data['preferred_locations']) && is_string($data['preferred_locations'])) {
            $data['preferred_locations'] = json_decode($data['preferred_locations'], true);
        }

        $validator = Validator::make($data, [
            'min_age' => 'sometimes|integer|min:18|max:100',
            'max_age' => 'sometimes|integer|min:18|max:100',
            'min_height' => 'sometimes|integer|min:100|max:250',
            'max_height' => 'sometimes|integer|min:100|max:250',
            'marital_status' => 'sometimes|string|in:never_married,divorced,widowed',
            'religion' => 'sometimes|string|max:255',
            'caste' => 'sometimes|string|max:255',
            'education' => 'sometimes|string|max:255',
            'occupation' => 'sometimes|string|max:255',
            'min_income' => 'sometimes|numeric|min:0',
            'max_income' => 'sometimes|numeric|min:0',
            'preferred_locations' => 'sometimes|array',
            'preferred_locations.*' => 'string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }

        // Update or create preferences
        $preferences = Preference::updateOrCreate(
            ['user_id' => $user->id],
            array_intersect_key($data, array_flip([
                'min_age',
                'max_age',
                'min_height',
                'max_height',
                'marital_status',
                'religion',
                'caste',
                'education',
                'occupation',
                'min_income',
                'max_income',
                'preferred_locations'
            ]))
        );

        return response()->json([
            'message' => 'Preferences updated successfully',
            'preferences' => $preferences
        ]);
    }

    /**
     * Get authenticated user's profile photos
     */
    public function getProfilePhotos(Request $request)
    {
        $user = $request->user();

        $photos = ProfilePhoto::where('user_id', $user->id)->orderBy('upload_date', 'desc')->get();

        return response()->json([
            'photos' => $photos
        ]);
    }

    /**
     * Upload a profile photo
     */
    public function uploadProfilePhoto(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', // 2MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }

        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $extension = $file->getClientOriginalExtension();
            $filename = 'profile_' . $user->id . '_' . now()->format('Y_m_d_H_i_s') . '.' . $extension;
            $path = $file->storeAs('profile_photos', $filename, 'public');
            $photoUrl = Storage::url($path);

            // If this is the first photo, make it primary
            $hasPhotos = ProfilePhoto::where('user_id', $user->id)->exists();
            $isPrimary = !$hasPhotos;

            $photo = ProfilePhoto::create([
                'user_id' => $user->id,
                'photo_url' => $photoUrl,
                'is_primary' => $isPrimary,
            ]);

            return response()->json([
                'message' => 'Photo uploaded successfully',
                'photo' => $photo
            ]);
        }

        return response()->json([
            'error' => 'No photo file provided'
        ], 400);
    }

    /**
     * Set a photo as primary
     */
    public function setPrimaryPhoto(Request $request, $photoId)
    {
        $user = $request->user();

        $photo = ProfilePhoto::where('user_id', $user->id)->where('id', $photoId)->first();

        if (!$photo) {
            return response()->json([
                'error' => 'Photo not found'
            ], 404);
        }

        // Set all photos to non-primary
        ProfilePhoto::where('user_id', $user->id)->update(['is_primary' => false]);

        // Set this one to primary
        $photo->update(['is_primary' => true]);

        return response()->json([
            'message' => 'Primary photo updated successfully',
            'photo' => $photo
        ]);
    }

    /**
     * Delete a profile photo
     */
    public function deleteProfilePhoto(Request $request, $photoId)
    {
        $user = $request->user();

        $photo = ProfilePhoto::where('user_id', $user->id)->where('id', $photoId)->first();

        if (!$photo) {
            return response()->json([
                'error' => 'Photo not found'
            ], 404);
        }

        // If it's primary, don't allow deletion if there are other photos
        if ($photo->is_primary) {
            $otherPhotos = ProfilePhoto::where('user_id', $user->id)->where('id', '!=', $photoId)->count();
            if ($otherPhotos > 0) {
                return response()->json([
                    'error' => 'Cannot delete primary photo. Set another photo as primary first.'
                ], 400);
            }
        }

        // Delete file from storage
        $path = str_replace('/storage/', '', $photo->photo_url);
        Storage::disk('public')->delete($path);

        $photo->delete();

        return response()->json([
            'message' => 'Photo deleted successfully'
        ]);
    }

    /**
     * Display a specific user's profile
     */
    public function show($id)
    {
        $user = User::with(['userProfile', 'familyDetails', 'preferences', 'profilePhotos'])
            ->whereNull('deleted_at') // Exclude soft deleted users
            ->find($id);

        if (!$user) {
            return response()->json([
                'error' => 'User not found'
            ], 404);
        }

        // Check interest status
        $viewingUser = request()->user();

        // Record profile view
        if ($viewingUser && $viewingUser->id != $id) {
            \App\Models\ProfileView::create([
                'viewer_id' => $viewingUser->id,
                'viewed_profile_id' => $id,
                'viewed_at' => now(),
            ]);
        }

        $interestSent = \App\Models\InterestSent::where('sender_id', $viewingUser->id)
            ->where('receiver_id', $id)
            ->first();

        $interestReceived = \App\Models\InterestSent::where('sender_id', $id)
            ->where('receiver_id', $viewingUser->id)
            ->first();

        return response()->json([
            'user' => $user,
            'interest_sent' => $interestSent,
            'interest_received' => $interestReceived
        ]);
    }

    /**
     * Get all users profiles based on filters/preferences
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Get user's preferences to filter profiles
        $preferences = $user->preferences;

        $query = User::with(['userProfile', 'profilePhotos'])
            ->where('id', '!=', $user->id) // Exclude current user
            ->where('status', 'active') // Only active users
            ->whereNull('deleted_at') // Exclude soft deleted users
            ->whereHas('userProfile', function ($q) {
                $q->where('is_active_verified', true);
            });


        // Apply preferences filter if available
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

            if ($preferences->religion) {
                $query->whereHas('userProfile', function ($q) use ($preferences) {
                    $q->where('religion', $preferences->religion);
                });
            }

            if ($preferences->caste) {
                $query->whereHas('userProfile', function ($q) use ($preferences) {
                    $q->where('caste', $preferences->caste);
                });
            }
        }

        $profiles = $query->paginate(10);

        return response()->json([
            'profiles' => $profiles
        ]);
    }
}
