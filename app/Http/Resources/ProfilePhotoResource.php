<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ProfilePhotoResource extends JsonResource
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
            'photo_url' => $this->photo_url,
            'full_photo_url' => $this->full_photo_url ?? null,
            'is_primary' => $this->is_primary,
            'upload_date' => $this->upload_date,
        ];
    }
}
