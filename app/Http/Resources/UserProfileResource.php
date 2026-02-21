<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'date_of_birth' => $this->date_of_birth,
            'age' => $this->date_of_birth ? \Carbon\Carbon::parse($this->date_of_birth)->age : null,
            'gender' => $this->gender,
            'height' => $this->height,
            'weight' => $this->weight,
            'drug_addiction' => $this->drug_addiction,
            'smoke' => $this->smoke,
            'alcohol' => $this->alcohol,
            'marital_status' => $this->marital_status,
            'religion_id' => $this->religion_id,
            'caste_id' => $this->caste_id,
            'sub_caste_id' => $this->sub_caste_id,
            'mother_tongue' => $this->mother_tongue,
            'profile_picture' => $this->profile_picture,
            'bio' => $this->bio,
            'education_id' => $this->education_id,
            'occupation_id' => $this->occupation_id,
            'annual_income' => $this->annual_income,
            'city' => $this->city,
            'district' => $this->district,
            'county' => $this->county,
            'state' => $this->state,
            'country' => $this->country,
            'postal_code' => $this->postal_code,
            'is_active_verified' => $this->is_active_verified,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,

            // Related models
            'religion_model' => new ReligionResource($this->whenLoaded('religionModel')),
            'caste_model' => new CasteResource($this->whenLoaded('casteModel')),
            'sub_caste_model' => new SubCasteResource($this->whenLoaded('subCasteModel')),
            'education_model' => new EducationResource($this->whenLoaded('educationModel')),
            'occupation_model' => new OccupationResource($this->whenLoaded('occupationModel')),
        ];
    }
}
