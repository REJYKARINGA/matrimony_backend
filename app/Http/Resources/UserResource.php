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

        $permissionRequestStatus = 'none';
        if ($currentUser && $currentUser->id !== $this->id) {
            $permRequest = \App\Models\ContactUnlockRequest::where('requester_id', $currentUser->id)
                ->where('target_user_id', $this->id)
                ->first();
            if ($permRequest) {
                $permissionRequestStatus = $permRequest->status;
            }
        }

        $canViewPhotos = true;
        $photoRequestStatus = null;
        
        if ($currentUser && $currentUser->id !== $this->id) {
            $photoRequestStatus = \App\Models\PhotoRequest::where('requester_id', $currentUser->id)
                ->where('receiver_id', $this->id)
                ->value('status');
        }

        if ($this->userProfile && $this->userProfile->hide_photos) {
            $canViewPhotos = false;
            if ($currentUser) {
                if ($currentUser->id === $this->id) {
                    $canViewPhotos = true;
                } else {
                    $ownerSentInterest = \App\Models\InterestSent::where('sender_id', $this->id)
                        ->where('receiver_id', $currentUser->id)
                        ->exists();

                    if ($ownerSentInterest || $photoRequestStatus === 'accepted') {
                        $canViewPhotos = true;
                    }
                }
            }
        }

            // Even if hidden, we might want to show a blurred version of the primary photo
            $primaryPhoto = \App\Models\ProfilePhoto::where('user_id', $this->id)
                ->where('is_primary', true)
                ->first();

            $profilePhotos = $canViewPhotos 
                ? ProfilePhotoResource::collection($this->whenLoaded('profilePhotos')) 
                : ($primaryPhoto ? [new ProfilePhotoResource($primaryPhoto)] : []);

            // Modify the userProfile resource conditionally
            $userProfileResource = null;
            if ($this->relationLoaded('userProfile') && $this->userProfile) {
                $clonedProfile = clone $this->userProfile;
                if (!$canViewPhotos) {
                    // Only provide the primary photo URL if it exists, for blurred display
                    $clonedProfile->profile_picture = $primaryPhoto ? $primaryPhoto->photo_url : null;
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
                'photo_request_status' => $photoRequestStatus,
                'photo_request_pending' => $photoRequestStatus === 'pending',
                'photo_request_rejected' => $photoRequestStatus === 'rejected',
            'distance' => $this->when(isset($this->distance), $this->distance),
            'personalities' => PersonalityResource::collection($this->whenLoaded('personalities')),
            'interests' => InterestResource::collection($this->whenLoaded('interests')),
            'is_photo_verified' => !$canViewPhotos || \App\Models\ProfilePhoto::where('user_id', $this->id)
                ->where('is_primary', true)
                ->where('is_verified', true)
                ->exists(),

            'contact_info' => [
                'is_contact_unlocked' => $currentUser && ($currentUser->id === $this->id || $hasUnlockedContact),
                'permission_request_status' => $permissionRequestStatus,
                'mandatory_permission_for_unlock' => (function() {
                    $setting = \App\Models\AdminSetting::first();
                    return $setting ? (bool) $setting->mandatory_permission_for_unlock : false;
                })(),
                'free_unlock_enabled' => (function() {
                    $setting = \App\Models\AdminSetting::first();
                    return $setting ? $setting->isFreeUnlockActive() : false;
                })(),
                'free_unlock_expires_at' => (function() {
                    $setting = \App\Models\AdminSetting::first();
                    return $setting && $setting->free_unlock_expires_at ? $setting->free_unlock_expires_at->toIso8601String() : null;
                })(),
                'active_festivals' => (function() {
                    return \App\Models\Festival::active()->get()->filter(function ($f) {
                        return $f->isCurrentlyActive();
                    })->values()->map(function ($f) {
                        $setting = \App\Models\AdminSetting::first();
                        $base = $setting ? $setting->getUnlockPrice() : 49;
                        $occurrence = $f->occurrences()->where('year', now()->year)->first();
                        return [
                            'id' => $f->id,
                            'celebration_name' => $f->celebration_name,
                            'offer_discount' => (float) $f->offer_discount,
                            'offer_discount_type' => $f->offer_discount_type,
                            'discount_value' => $f->getDiscountValue($base),
                            'ends_at' => $occurrence ? $occurrence->end_at->toIso8601String() : null,
                        ];
                    });
                })(),
                'unlock_price' => (function() {
                    $setting = \App\Models\AdminSetting::first();
                    return $setting ? $setting->getUnlockPrice() : 49;
                })(),
                'discounted_price' => (function() {
                    $setting = \App\Models\AdminSetting::first();
                    return $setting ? $setting->getDiscountedPrice() : 49;
                })(),
            ],
            'reports_count' => (function() {
                try {
                    return \App\Models\UserReport::where('reported_id', $this->id)->count();
                } catch (\Exception $e) {
                    return 0;
                }
            })(),
            'is_reported_by_me' => (function() use ($currentUser) {
                if (!$currentUser) return false;
                try {
                    return \App\Models\UserReport::where('reporter_id', $currentUser->id)
                        ->where('reported_id', $this->id)
                        ->exists();
                } catch (\Exception $e) {
                    return false;
                }
            })(),
            'last_active_at' => $this->last_active_at ? $this->last_active_at->toDateTimeString() : null,

            // Hide sensitive fields completely - never return these
            // password, role, status, email_verified, phone_verified, deleted_at, last_login
        ];
    }
}
