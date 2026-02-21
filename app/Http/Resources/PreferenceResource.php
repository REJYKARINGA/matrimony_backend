<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PreferenceResource extends JsonResource
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
            'min_age' => $this->min_age,
            'max_age' => $this->max_age,
            'min_height' => $this->min_height,
            'max_height' => $this->max_height,
            'marital_status' => $this->marital_status,
            'religion_id' => $this->religion_id,
            'religion_name' => $this->religion?->name,
            'caste_ids' => $this->caste_ids,
            'sub_caste_ids' => $this->sub_caste_ids,
            'education_ids' => $this->education_ids,
            'occupation_ids' => $this->occupation_ids,
            'min_income' => $this->min_income,
            'max_income' => $this->max_income,
            'max_distance' => $this->max_distance,
            'preferred_locations' => $this->preferred_locations,
            'drug_addiction' => $this->drug_addiction,
            'smoke' => $this->smoke,
            'alcohol' => $this->alcohol,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
