<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FamilyDetailResource extends JsonResource
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
        if ($currentUser && $this->user_id) {
            $hasUnlockedContact = \App\Models\ContactUnlock::where('user_id', $currentUser->id)
                ->where('unlocked_user_id', $this->user_id)
                ->exists();
        }

        // Only show family details if contact is unlocked or it's the user's own profile
        $showFamilyDetails = $currentUser && ($currentUser->id === $this->user_id || $hasUnlockedContact);

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,

            // Always show basic family info
            'family_type' => $this->family_type,
            'family_status' => $this->family_status,
            'family_location' => $this->family_location,
            'siblings' => $this->siblings,
            'father_occupation' => $this->father_occupation,
            'father_alive' => $this->father_alive,
            'mother_occupation' => $this->mother_occupation,
            'mother_alive' => $this->mother_alive,
            'elder_sister' => $this->elder_sister,
            'elder_brother' => $this->elder_brother,
            'younger_sister' => $this->younger_sister,
            'younger_brother' => $this->younger_brother,
            'twin_type' => $this->twin_type,
            'is_disabled' => $this->is_disabled,
            'guardian' => $this->guardian,
            'show' => $this->show,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
