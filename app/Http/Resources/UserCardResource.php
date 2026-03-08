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
            'profile_picture' => $profile ? $profile->profile_picture : null,
            'distance' => $this->when(isset($this->distance), function () {
                return round($this->distance, 1);
            }),
            'is_contact_unlocked' => $currentUser && ($currentUser->id === $this->id || $hasUnlockedContact),
            'is_active_verified' => $profile ? (bool) $profile->is_active_verified : false,
        ];
    }
}
