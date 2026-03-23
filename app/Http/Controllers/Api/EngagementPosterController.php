<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\EngagementPoster;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Support\Facades\Storage;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Support\Facades\Log;

class EngagementPosterController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $posters = EngagementPoster::with('user')->paginate(15);

        return response()->json([
            'engagement_posters' => $posters
        ]);
    }

    /**
     * Display the authenticated user's poster.
     */
    public function myPoster()
    {
        $poster = EngagementPoster::with('user')->where('user_id', auth()->id())->first();

        if (!$poster) {
            return response()->json([
                'error' => 'Engagement poster not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'engagement_poster' => $poster
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'poster_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'engagement_date' => 'required|date',
            'announcement_title' => 'required|string|max:255',
            'announcement_message' => 'required|string',
            'partner_matrimony_id' => 'required|string|exists:users,matrimony_id',
            'is_active' => 'nullable|string', // Accept string from Multipart
            'is_verified' => 'nullable|string',
            'display_expire_at' => 'required|date',
        ]);

        $partnerUser = User::where('matrimony_id', $request->partner_matrimony_id)->first();
        if ($partnerUser && $partnerUser->id === auth()->id()) {
            return response()->json([
                'error' => 'You cannot enter your own Matrimony ID as a partner.'
            ], 400);
        }

        // Validate: opposite gender, same religion, not already confirmed
        $validationError = $this->validatePartnerCompatibility(
            $request->partner_matrimony_id,
            auth()->id()
        );
        if ($validationError) return $validationError;

        $data = $request->except('poster_image');
        $data['user_id'] = auth()->id();
        $data['partner_status'] = 'pending';

        // Handle Booleans from Multipart strings
        $data['is_active'] = filter_var($request->is_active ?? true, FILTER_VALIDATE_BOOLEAN);
        $data['is_verified'] = filter_var($request->is_verified ?? false, FILTER_VALIDATE_BOOLEAN);

        // Handle Image Upload
        if ($request->hasFile('poster_image')) {
            try {
                $uploadResult = cloudinary()->uploadApi()->upload($request->file('poster_image')->getRealPath(), [
                    'folder' => 'matrimony/engagement_posters/' . auth()->id(),
                    'public_id' => 'engagement_poster_' . auth()->id() . '_' . now()->timestamp,
                ]);
                $data['poster_image'] = $uploadResult['secure_url'];
            } catch (\Exception $e) {
                Log::error('Cloudinary upload error: ' . $e->getMessage());
                return response()->json([
                    'error' => 'Failed to upload engagement poster',
                    'message' => $e->getMessage()
                ], 500);
            }
        }

        $poster = EngagementPoster::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Engagement poster created successfully.',
            'engagement_poster' => $poster->load('user')
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $poster = EngagementPoster::with('user')->find($id);

        if (!$poster) {
            return response()->json([
                'error' => 'Engagement poster not found'
            ], 404);
        }

        return response()->json([
            'engagement_poster' => $poster
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $poster = EngagementPoster::find($id);

        if (!$poster) {
            return response()->json([
                'error' => 'Engagement poster not found'
            ], 404);
        }

        $request->validate([
            'poster_image'          => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'engagement_date'       => 'nullable|date',
            'announcement_title'    => 'nullable|string|max:255',
            'announcement_message'  => 'nullable|string',
            'partner_matrimony_id'  => 'nullable|string|exists:users,matrimony_id', // Validate partner ID exists
            'is_active'             => 'nullable|string',
            'is_verified'           => 'nullable|string',
            'display_expire_at'     => 'nullable|date',
        ]);

        // If partner_matrimony_id is changing, validate not self
        if ($request->has('partner_matrimony_id') && $request->partner_matrimony_id) {
            $partnerUser = User::where('matrimony_id', $request->partner_matrimony_id)->first();
            if ($partnerUser && $partnerUser->id === auth()->id()) {
                return response()->json([
                    'error' => 'You cannot enter your own Matrimony ID as a partner.'
                ], 400);
            }

            // Validate: opposite gender, same religion, not already confirmed (excluding current poster)
            $validationError = $this->validatePartnerCompatibility(
                $request->partner_matrimony_id,
                auth()->id(),
                $poster->id
            );
            if ($validationError) return $validationError;

            // Reset partner_status to pending when partner ID changes
            if ($request->partner_matrimony_id !== $poster->partner_matrimony_id) {
                $poster->partner_status = 'pending';
            }
        }

        $data = $request->except('poster_image');

        // Handle Booleans from Multipart strings if present
        if ($request->has('is_active')) {
            $data['is_active'] = filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN);
        }
        if ($request->has('is_verified')) {
            $data['is_verified'] = filter_var($request->is_verified, FILTER_VALIDATE_BOOLEAN);
        }

        // Handle Image Upload
        if ($request->hasFile('poster_image')) {
            // Delete old image if it exists and is on Cloudinary
            if ($poster->poster_image && str_contains($poster->poster_image, 'res.cloudinary.com')) {
                try {
                    $urlParts = parse_url($poster->poster_image);
                    $pathParts = explode('/', trim($urlParts['path'], '/'));
                    $publicIdWithExt = end($pathParts);
                    $publicId = pathinfo($publicIdWithExt, PATHINFO_FILENAME);
                    cloudinary()->uploadApi()->destroy('matrimony/engagement_posters/' . $poster->user_id . '/' . $publicId);
                } catch (\Exception $e) {
                    Log::warning('Failed to delete old engagement poster from Cloudinary: ' . $e->getMessage());
                }
            } elseif ($poster->poster_image) {
                // local fallback
                $oldImagePath = str_replace('/storage/', '', $poster->poster_image);
                Storage::disk('public')->delete($oldImagePath);
            }
            
            try {
                $uploadResult = cloudinary()->uploadApi()->upload($request->file('poster_image')->getRealPath(), [
                    'folder' => 'matrimony/engagement_posters/' . $poster->user_id,
                    'public_id' => 'engagement_poster_' . $poster->user_id . '_' . now()->timestamp,
                ]);
                $data['poster_image'] = $uploadResult['secure_url'];
            } catch (\Exception $e) {
                Log::error('Cloudinary upload error: ' . $e->getMessage());
                return response()->json([
                    'error' => 'Failed to upload engagement poster',
                    'message' => $e->getMessage()
                ], 500);
            }
        } elseif ($request->has('poster_image') && $request->poster_image === null) {
            // If poster_image is explicitly set to null, delete the old image
            if ($poster->poster_image && str_contains($poster->poster_image, 'res.cloudinary.com')) {
                try {
                    $urlParts = parse_url($poster->poster_image);
                    $pathParts = explode('/', trim($urlParts['path'], '/'));
                    $publicIdWithExt = end($pathParts);
                    $publicId = pathinfo($publicIdWithExt, PATHINFO_FILENAME);
                    cloudinary()->uploadApi()->destroy('matrimony/engagement_posters/' . $poster->user_id . '/' . $publicId);
                } catch (\Exception $e) {
                    Log::warning('Failed to delete old engagement poster from Cloudinary: ' . $e->getMessage());
                }
            } elseif ($poster->poster_image) {
                $oldImagePath = str_replace('/storage/', '', $poster->poster_image);
                Storage::disk('public')->delete($oldImagePath);
            }
            $data['poster_image'] = null;
        }

        $poster->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Engagement poster updated successfully.',
            'engagement_poster' => $poster->load('user')
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $poster = EngagementPoster::find($id);

        if (!$poster) {
            return response()->json([
                'error' => 'Engagement poster not found'
            ], 404);
        }

        // Delete from Cloudinary or local storage
        if ($poster->poster_image && str_contains($poster->poster_image, 'res.cloudinary.com')) {
            try {
                $urlParts = parse_url($poster->poster_image);
                $pathParts = explode('/', trim($urlParts['path'], '/'));
                $publicIdWithExt = end($pathParts);
                $publicId = pathinfo($publicIdWithExt, PATHINFO_FILENAME);
                cloudinary()->uploadApi()->destroy('matrimony/engagement_posters/' . $poster->user_id . '/' . $publicId);
            } catch (\Exception $e) {
                Log::warning('Failed to delete from Cloudinary: ' . $e->getMessage());
            }
        } elseif ($poster->poster_image) {
            $oldImagePath = str_replace('/storage/', '', $poster->poster_image);
            Storage::disk('public')->delete($oldImagePath);
        }

        $poster->delete();

        return response()->json([
            'success' => true,
            'message' => 'Engagement poster deleted successfully'
        ]);
    }

    /**
     * Partner confirms or rejects the engagement.
     */
    public function respondToEngagement(Request $request, string $id)
    {
        $request->validate([
            'status' => 'required|in:confirmed,rejected'
        ]);

        $poster = EngagementPoster::find($id);

        if (!$poster) {
            return response()->json([
                'error' => 'Engagement poster not found'
            ], 404);
        }

        // Check if the authenticated user is the requested partner
        if (auth()->user()->matrimony_id !== $poster->partner_matrimony_id) {
            return response()->json([
                'error' => 'You are not authorized to respond to this engagement announcement.'
            ], 403);
        }

        $poster->partner_status = $request->status;
        $poster->save();

        return response()->json([
            'success' => true,
            'message' => 'You have ' . $request->status . ' the engagement.',
            'engagement_poster' => $poster
        ]);
    }

    /**
     * Validate partner compatibility:
     *  - Must be opposite gender
     *  - Must share the same religion
     *  - Must not already be confirmed in another engagement poster
     *
     * @param string $partnerMatrimonyId
     * @param int    $authUserId
     * @param int|null $excludePosterId  Exclude this poster ID from the "already confirmed" check (for updates)
     */
    private function validatePartnerCompatibility(string $partnerMatrimonyId, int $authUserId, ?int $excludePosterId = null)
    {
        // Load the partner user with their profile
        $partnerUser = User::where('matrimony_id', $partnerMatrimonyId)->with('userProfile')->first();

        if (!$partnerUser) {
            return response()->json([
                'error'  => 'Partner not found.',
                'reason' => 'No user found with Matrimony ID ' . $partnerMatrimonyId . '. Please verify the ID and try again.',
            ], 422);
        }

        // Load the authenticated user with their profile
        $authUser = User::with('userProfile')->find($authUserId);

        $authProfile    = $authUser?->userProfile;
        $partnerProfile = $partnerUser->userProfile;

        // --- Gender check (opposite gender required) ---
        $authGender    = $authProfile?->gender;
        $partnerGender = $partnerProfile?->gender;

        $oppositeGenders = [
            'male'   => 'female',
            'female' => 'male',
        ];

        if ($authGender && $partnerGender) {
            $expectedPartnerGender = $oppositeGenders[strtolower($authGender)] ?? null;
            if ($expectedPartnerGender && strtolower($partnerGender) !== $expectedPartnerGender) {
                return response()->json([
                    'error'  => 'Gender mismatch.',
                    'reason' => 'Engagement is only allowed between a male and a female. The Matrimony ID ' . $partnerMatrimonyId . ' does not have the opposite gender.',
                ], 422);
            }
        }

        // --- Religion check (same religion required) ---
        $authReligionId    = $authProfile?->religion_id;
        $partnerReligionId = $partnerProfile?->religion_id;

        if ($authReligionId && $partnerReligionId && $authReligionId !== $partnerReligionId) {
            return response()->json([
                'error'  => 'Religion mismatch.',
                'reason' => 'The partner\'s religion does not match yours. Engagement posters require both partners to share the same religion.',
            ], 422);
        }

        // --- Already confirmed in another poster check ---
        $query = EngagementPoster::where('partner_matrimony_id', $partnerMatrimonyId)
            ->where('partner_status', 'confirmed');

        if ($excludePosterId) {
            $query->where('id', '!=', $excludePosterId);
        }

        if ($query->exists()) {
            return response()->json([
                'error'  => 'Partner already confirmed in another engagement.',
                'reason' => 'The Matrimony ID ' . $partnerMatrimonyId . ' has already confirmed an engagement with someone else. Please verify and use the correct partner ID.',
            ], 422);
        }

        return null; // All checks passed
    }
}
