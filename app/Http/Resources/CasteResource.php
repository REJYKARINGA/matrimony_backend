<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CasteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'religion_id' => $this->religion_id,
            'name' => $this->name,
            'is_active' => $this->is_active,
            'order_number' => $this->order_number,
        ];
    }
}
