<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class UserCardResource extends JsonResource
{
    /**
     * Transform the resource into an array focused on minimizing bandwidth for card display.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $currentUser = $request->user();
        $profile = $this->userProfile;

        $hasUnlockedContact = false;
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
        $hasPhotoRequestPending = false;
        $hasPhotoRequestRejected = false;
        $viewerGender = $currentUser?->userProfile?->gender;
        
        if ($currentUser && $currentUser->id !== $this->id) {
            $photoRequestStatus = \App\Models\PhotoRequest::where('requester_id', $currentUser->id)
                ->where('receiver_id', $this->id)
                ->value('status');
            
            if ($photoRequestStatus === 'pending') {
                $hasPhotoRequestPending = true;
            }
            if ($photoRequestStatus === 'rejected') {
                $hasPhotoRequestRejected = true;
            }
        }

        if ($profile && $profile->hide_photos) {
            $canViewPhotos = false;
            if ($currentUser) {
                if ($currentUser->id === $this->id) {
                    $canViewPhotos = true;
                } else {
                    $ownerSentInterest = \App\Models\InterestSent::where('sender_id', $this->id)
                        ->where('receiver_id', $currentUser->id)
                        ->exists();

                    if ($ownerSentInterest || ($photoRequestStatus ?? null) === 'accepted') {
                        $canViewPhotos = true;
                    }
                }
            }
        }

        $primaryPhoto = $this->profilePhotos()->where('is_primary', true)->first() ?? $this->profilePhotos()->first();
        $isPhotoVerified = $primaryPhoto ? (bool) $primaryPhoto->is_verified : true;

        return [
            'id' => $this->id,
            'matrimony_id' => $this->matrimony_id,
            'age' => $profile && $profile->date_of_birth ? Carbon::parse($profile->date_of_birth)->age : null,
            'height' => $profile ? $profile->height : null,
            'marital_status' => $profile ? $profile->marital_status : null,
            'caste' => $profile && $profile->casteModel ? $profile->casteModel->name : null,
            'education' => $profile && $profile->educationModel ? $profile->educationModel->name : null,
            'occupation' => $profile && $profile->occupationModel ? $profile->occupationModel->name : null,
            'city' => $profile ? $profile->city : null,
            'present_city' => $profile ? $profile->present_city : null,
            'present_country' => $profile ? $profile->present_country : null,
            'profile_picture' => $profile ? ($canViewPhotos ? $profile->profile_picture : ($primaryPhoto ? $primaryPhoto->photo_url : null)) : null,
            'has_hidden_photos' => !$canViewPhotos,
            'is_photo_verified' => $isPhotoVerified,
            'photo_request_pending' => $hasPhotoRequestPending,
            'photo_request_rejected' => $hasPhotoRequestRejected,
            'distance' => $this->when(isset($this->distance), function () {
                return round($this->distance, 1);
            }),
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
            'active_festivals' => (function() use ($viewerGender) {
                return \App\Models\Festival::active()->get()->filter(function ($f) use ($viewerGender) {
                    if (!$f->isCurrentlyActive()) return false;
                    return $f->matchesGender($viewerGender);
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
            'discounted_price' => (function() use ($viewerGender) {
                $setting = \App\Models\AdminSetting::first();
                return $setting ? $setting->getDiscountedPrice($viewerGender) : 49;
            })(),
            'is_contact_unlocked' => $currentUser && ($currentUser->id === $this->id || $hasUnlockedContact),
            'permission_request_status' => $permissionRequestStatus,
            'is_identity_verified' => $profile ? (bool) $profile->is_identity_verified : false,
            'is_profile_active' => $profile ? (bool) $profile->is_profile_active : false,
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
        ];
    }
}
