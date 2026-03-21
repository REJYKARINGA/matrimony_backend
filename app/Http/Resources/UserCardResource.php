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

        $canViewPhotos = true;
        $hasPhotoRequestPending = false;
        
        if ($profile && $profile->hide_photos) {
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
            'profile_picture' => ($profile && $canViewPhotos) ? $profile->profile_picture : null,
            'has_hidden_photos' => !$canViewPhotos,
            'is_photo_verified' => $isPhotoVerified,
            'photo_request_pending' => $hasPhotoRequestPending,
            'distance' => $this->when(isset($this->distance), function () {
                return round($this->distance, 1);
            }),
            'is_contact_unlocked' => $currentUser && ($currentUser->id === $this->id || $hasUnlockedContact),
            'is_active_verified' => $profile ? (bool) $profile->is_active_verified : false,
            'reports_count' => (function() {
                try {
                    return \App\Models\UserReport::where('reported_id', $this->id)->count();
                } catch (\Exception $e) {
                    return 0; // Fallback if table doesn't exist yet
                }
            })(),
        ];
    }
}
