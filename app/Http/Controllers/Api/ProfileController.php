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

        $user->load([
            'userProfile.religionModel',
            'userProfile.casteModel',
            'userProfile.subCasteModel',
            'userProfile.educationModel',
            'userProfile.occupationModel',
            'familyDetails',
            'preferences',
            'profilePhotos',
            'verification'
        ]);

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
            'marital_status' => 'sometimes|string|in:never_married,divorced,nikkah_divorced,widowed',
            'religion_id' => 'nullable|exists:religions,id',
            'caste_id' => 'nullable|exists:castes,id',
            'sub_caste_id' => 'nullable|exists:sub_castes,id',
            'mother_tongue' => 'sometimes|string|max:255',
            'profile_picture' => 'sometimes|string|max:255',
            'bio' => 'sometimes|string',
            'education_id' => 'nullable|exists:education,id',
            'occupation_id' => 'nullable|exists:occupations,id',
            'annual_income' => 'sometimes|numeric',
            'city' => 'sometimes|string|max:255',
            'district' => 'sometimes|string|in:Thiruvananthapuram,Kollam,Pathanamthitta,Alappuzha,Kottayam,Idukki,Ernakulam,Thrissur,Palakkad,Malappuram,Kozhikode,Wayanad,Kannur,Kasaragod',
            'county' => 'nullable|string|max:255',
            'state' => 'sometimes|string|max:255',
            'country' => 'sometimes|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'drug_addiction' => 'sometimes|boolean',
            'smoke' => 'sometimes|string|in:never,occasionally,regularly',
            'alcohol' => 'sometimes|string|in:never,occasionally,regularly',
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

        // Find existing profile or create new instance
        $userProfile = UserProfile::firstOrNew(['user_id' => $user->id]);

        // Access the old (current) profile data before update for comparison if needed, 
        // but getDirty() works on the model instance after fill().

        $data = $request->only([
            'first_name',
            'last_name',
            'date_of_birth',
            'gender',
            'height',
            'weight',
            'marital_status',
            'religion_id',
            'caste_id',
            'sub_caste_id',
            'mother_tongue',
            'profile_picture',
            'bio',
            'education_id',
            'occupation_id',
            'annual_income',
            'city',
            'district',
            'county',
            'state',
            'country',
            'postal_code',
            'drug_addiction',
            'smoke',
            'alcohol'
        ]);

        $userProfile->fill($data);

        // Check if there are any changes
        if ($userProfile->isDirty()) {
            // Get the keys of the attributes that have changed
            $changes = array_keys($userProfile->getDirty());

            // If the profile is already unverified and has stored changes, merge them
            if ($userProfile->exists && !$userProfile->is_active_verified && !empty($userProfile->changed_fields)) {
                $existingChanges = $userProfile->changed_fields;
                // Ensure existingChanges is an array (handled by cast, but safe check)
                if (is_array($existingChanges)) {
                    $changes = array_unique(array_merge($existingChanges, $changes));
                }
            }

            // Update the changed_fields and set is_active_verified to false
            $userProfile->changed_fields = array_values($changes);
            $userProfile->is_active_verified = false;
        } elseif (!$userProfile->exists) {
            // New profile creation
            $userProfile->is_active_verified = false;
            $userProfile->changed_fields = null; // Or all fields? Keeping null for new profiles.
        }

        $userProfile->save();

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
            'is_disabled' => 'sometimes|boolean',
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
                'is_disabled',
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

        // Handle preferred_locations and caste if sent as JSON string
        if (isset($data['preferred_locations']) && is_string($data['preferred_locations'])) {
            $data['preferred_locations'] = json_decode($data['preferred_locations'], true);
        }
        if (isset($data['caste_ids']) && is_string($data['caste_ids'])) {
            $data['caste_ids'] = json_decode($data['caste_ids'], true);
        }
        if (isset($data['sub_caste_ids']) && is_string($data['sub_caste_ids'])) {
            $data['sub_caste_ids'] = json_decode($data['sub_caste_ids'], true);
        }
        if (isset($data['education_ids']) && is_string($data['education_ids'])) {
            $data['education_ids'] = json_decode($data['education_ids'], true);
        }
        if (isset($data['occupation_ids']) && is_string($data['occupation_ids'])) {
            $data['occupation_ids'] = json_decode($data['occupation_ids'], true);
        }
        if (isset($data['smoke']) && is_string($data['smoke']) && (str_starts_with($data['smoke'], '[') || str_starts_with($data['smoke'], '{'))) {
            $data['smoke'] = json_decode($data['smoke'], true);
        }
        if (isset($data['alcohol']) && is_string($data['alcohol']) && (str_starts_with($data['alcohol'], '[') || str_starts_with($data['alcohol'], '{'))) {
            $data['alcohol'] = json_decode($data['alcohol'], true);
        }

        $validator = Validator::make($data, [
            'min_age' => 'sometimes|integer|min:18|max:100',
            'max_age' => 'sometimes|integer|min:18|max:100',
            'min_height' => 'sometimes|integer|min:100|max:250',
            'max_height' => 'sometimes|integer|min:100|max:250',
            'marital_status' => 'sometimes|string|in:never_married,divorced,nikkah_divorced,widowed',
            'religion_id' => 'nullable|exists:religions,id',
            'caste_ids' => 'sometimes|array',
            'sub_caste_ids' => 'sometimes|array',
            'education_ids' => 'sometimes|array',
            'occupation_ids' => 'sometimes|array',
            'min_income' => 'sometimes|numeric|min:0',
            'max_income' => 'sometimes|numeric|min:0',
            'max_distance' => 'sometimes|integer|min:1|max:500',
            'preferred_locations' => 'sometimes|array',
            'preferred_locations.*' => 'string|max:255',
            'drug_addiction' => 'sometimes|string|in:any,yes,no',
            'smoke' => 'sometimes|array',
            'smoke.*' => 'string|in:never,occasionally,regularly',
            'alcohol' => 'sometimes|array',
            'alcohol.*' => 'string|in:never,occasionally,regularly',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }

        if (isset($data['education_ids']) && is_array($data['education_ids'])) {
            \App\Models\Education::whereIn('id', $data['education_ids'])->increment('popularity_count');
        }
        if (isset($data['occupation_ids']) && is_array($data['occupation_ids'])) {
            \App\Models\Occupation::whereIn('id', $data['occupation_ids'])->increment('popularity_count');
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
                'religion_id',
                'caste_ids',
                'sub_caste_ids',
                'education_ids',
                'occupation_ids',
                'min_income',
                'max_income',
                'max_distance',
                'preferred_locations',
                'drug_addiction',
                'smoke',
                'alcohol'
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
                'photo' => $photo->setAppends(['full_photo_url'])
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
        $user = User::with(['userProfile', 'familyDetails', 'preferences', 'profilePhotos', 'verification'])
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

        // Add distance calculation
        if (
            $viewingUser && $viewingUser->userProfile && $viewingUser->userProfile->latitude &&
            $user->userProfile && $user->userProfile->latitude
        ) {
            $user->distance = $this->calculateDistance(
                $viewingUser->userProfile->latitude,
                $viewingUser->userProfile->longitude,
                $user->userProfile->latitude,
                $user->userProfile->longitude
            );
        }

        return response()->json([
            'user' => $user,
            'interest_sent' => $interestSent,
            'interest_received' => $interestReceived
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

            if ($preferences->religion_id) {
                $query->whereHas('userProfile', function ($q) use ($preferences) {
                    $q->where('religion_id', $preferences->religion_id);
                });
            }

            if ($preferences->caste_ids && is_array($preferences->caste_ids)) {
                $query->whereHas('userProfile', function ($q) use ($preferences) {
                    $q->whereIn('caste_id', $preferences->caste_ids);
                });
            }

            if ($preferences->sub_caste_ids && is_array($preferences->sub_caste_ids)) {
                $query->whereHas('userProfile', function ($q) use ($preferences) {
                    $q->whereIn('sub_caste_id', $preferences->sub_caste_ids);
                });
            }

            if ($preferences->education_ids && is_array($preferences->education_ids)) {
                $query->whereHas('userProfile', function ($q) use ($preferences) {
                    $q->whereIn('education_id', $preferences->education_ids);
                });
            }

            if ($preferences->occupation_ids && is_array($preferences->occupation_ids)) {
                $query->whereHas('userProfile', function ($q) use ($preferences) {
                    $q->whereIn('occupation_id', $preferences->occupation_ids);
                });
            }

            if ($preferences->marital_status) {
                $query->whereHas('userProfile', function ($q) use ($preferences) {
                    $q->where('marital_status', $preferences->marital_status);
                });
            }

            if ($preferences->drug_addiction && $preferences->drug_addiction != 'any') {
                $query->whereHas('userProfile', function ($q) use ($preferences) {
                    $q->where('drug_addiction', $preferences->drug_addiction == 'yes');
                });
            }

            if ($preferences->smoke && is_array($preferences->smoke)) {
                $query->whereHas('userProfile', function ($q) use ($preferences) {
                    $q->whereIn('smoke', $preferences->smoke);
                });
            }

            if ($preferences->alcohol && is_array($preferences->alcohol)) {
                $query->whereHas('userProfile', function ($q) use ($preferences) {
                    $q->whereIn('alcohol', $preferences->alcohol);
                });
            }
        }

        $profiles = $query->paginate(10);

        return response()->json([
            'profiles' => $profiles
        ]);
    }
}
