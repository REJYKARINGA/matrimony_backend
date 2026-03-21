<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\UserSubscription;
use App\Models\InterestSent;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $currentUser = $request->user();
        $hasUnlockedContact = false;

        // Check if current user has unlocked this profile's contact
        if ($currentUser && $currentUser->id !== $this->id) {
            $hasUnlockedContact = \App\Models\ContactUnlock::where('user_id', $currentUser->id)
                ->where('unlocked_user_id', $this->id)
                ->exists();
        }

        $canViewPhotos = true;
        $hasPhotoRequestPending = false;
        
        if ($this->userProfile && $this->userProfile->hide_photos) {
            $canViewPhotos = false;
            if ($currentUser) {
                if ($currentUser->id === $this->id) {
                    $canViewPhotos = true;
                } else {
                    $ownerSentInterest = \App\Models\InterestSent::where('sender_id', $this->id)
                        ->where('receiver_id', $currentUser->id)
                        ->exists();

                    $photoRequestStatus = \App\Models\PhotoRequest::where('requester_id', $currentUser->id)
                        ->where('receiver_id', $this->id)
                        ->value('status');

                    if ($ownerSentInterest || $photoRequestStatus === 'accepted') {
                        $canViewPhotos = true;
                    }
                    if ($photoRequestStatus === 'pending') {
                        $hasPhotoRequestPending = true;
                    }
                }
            }
        }

        $profilePhotos = $canViewPhotos ? ProfilePhotoResource::collection($this->whenLoaded('profilePhotos')) : [];

        // Modify the userProfile resource conditionally
        $userProfileResource = null;
        if ($this->relationLoaded('userProfile') && $this->userProfile) {
            $clonedProfile = clone $this->userProfile;
            if (!$canViewPhotos) {
                $clonedProfile->profile_picture = null;
            }
            $userProfileResource = new UserProfileResource($clonedProfile);
        }

        return [
            'id' => $this->id,
            'matrimony_id' => $this->matrimony_id,
            'user_profile' => $userProfileResource,
            'family_details' => new FamilyDetailResource($this->whenLoaded('familyDetails')),
            'preferences' => new PreferenceResource($this->whenLoaded('preferences')),
            'profile_photos' => $profilePhotos,
            'has_hidden_photos' => !$canViewPhotos,
            'photo_request_pending' => $hasPhotoRequestPending,
            'distance' => $this->when(isset($this->distance), $this->distance),
            'personalities' => PersonalityResource::collection($this->whenLoaded('personalities')),
            'interests' => InterestResource::collection($this->whenLoaded('interests')),

            'contact_info' => [
                'is_contact_unlocked' => $currentUser && ($currentUser->id === $this->id || $hasUnlockedContact),
            ],
            'reports_count' => (function() {
                try {
                    return \App\Models\UserReport::where('reported_id', $this->id)->count();
                } catch (\Exception $e) {
                    return 0; // Fallback if table doesn't exist yet
                }
            })(),

            // Hide sensitive fields completely - never return these
            // password, role, status, email_verified, phone_verified, deleted_at, last_login
        ];
    }
}
