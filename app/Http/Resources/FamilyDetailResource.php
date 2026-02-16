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
            'siblings' => $showFamilyDetails ? $this->siblings : null,
            
            // Only show detailed info if contact is unlocked
            'father_name' => $showFamilyDetails ? $this->father_name : null,
            'father_occupation' => $showFamilyDetails ? $this->father_occupation : null,
            'father_alive' => $showFamilyDetails ? $this->father_alive : null,
            'mother_name' => $showFamilyDetails ? $this->mother_name : null,
            'mother_occupation' => $showFamilyDetails ? $this->mother_occupation : null,
            'mother_alive' => $showFamilyDetails ? $this->mother_alive : null,
            'elder_sister' => $showFamilyDetails ? $this->elder_sister : null,
            'elder_brother' => $showFamilyDetails ? $this->elder_brother : null,
            'younger_sister' => $showFamilyDetails ? $this->younger_sister : null,
            'younger_brother' => $showFamilyDetails ? $this->younger_brother : null,
            'twin_type' => $showFamilyDetails ? $this->twin_type : null,
            'is_disabled' => $showFamilyDetails ? $this->is_disabled : null,
            'guardian' => $showFamilyDetails ? $this->guardian : null,
            'show' => $showFamilyDetails ? $this->show : null,
            
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
