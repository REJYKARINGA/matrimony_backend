<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubCasteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'caste_id' => $this->caste_id,
            'name' => $this->name,
            'is_active' => $this->is_active,
            'order_number' => $this->order_number,
        ];
    }
}
