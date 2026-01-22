<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class ProfileView extends Model
{
    protected $table = 'profile_views';
    protected $fillable = [
        'viewer_id',
        'viewed_profile_id',
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
    ];

    /**
     * Relationship with viewer (user)
     */
    public function viewer()
    {
        return $this->belongsTo(User::class, 'viewer_id');
    }

    /**
     * Relationship with viewed profile (user)
     */
    public function viewedProfile()
    {
        return $this->belongsTo(User::class, 'viewed_profile_id');
    }
}
