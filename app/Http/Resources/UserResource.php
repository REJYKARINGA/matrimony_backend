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

        return [
            'id' => $this->id,
            'matrimony_id' => $this->matrimony_id,
            'user_profile' => new UserProfileResource($this->whenLoaded('userProfile')),
            'profile_photos' => ProfilePhotoResource::collection($this->whenLoaded('profilePhotos')),
            'distance' => $this->when(isset($this->distance), $this->distance),
            
            // Contact information - only shown if unlocked or it's the user's own profile
            'contact_info' => $this->when(
                $currentUser && ($currentUser->id === $this->id || $hasUnlockedContact),
                fn() => [
                    'email' => $this->email,
                    'phone' => $this->phone,
                    'is_contact_unlocked' => $hasUnlockedContact || $currentUser->id === $this->id,
                ]
            ),
            
            // Hide sensitive fields completely - never return these
            // password, role, status, email_verified, phone_verified, deleted_at, last_login
        ];
    }
}
