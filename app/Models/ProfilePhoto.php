<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class ProfilePhoto extends Model
{
    public $timestamps = false;

    protected $table = 'profile_photos';
    protected $fillable = [
        'user_id',
        'photo_url',
        'is_primary',
        'is_verified',
        'verified_by',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_verified' => 'boolean',
        'upload_date' => 'datetime',
        'verification_date' => 'datetime',
    ];

    protected $appends = ['full_photo_url'];

    /**
     * Get the full URL for the photo
     */
    public function getFullPhotoUrlAttribute()
    {
        if (!$this->photo_url)
            return null;
        if (str_starts_with($this->photo_url, 'http'))
            return $this->photo_url;
        return config('app.url') . $this->photo_url;
    }

    /**
     * Relationship with user
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relationship with verifier (user)
     */
    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
