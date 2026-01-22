<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class ShortlistedProfile extends Model
{
    protected $table = 'shortlisted_profiles';
    protected $fillable = [
        'user_id',
        'shortlisted_user_id',
        'notes',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * Relationship with user
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relationship with shortlisted user
     */
    public function shortlistedUser()
    {
        return $this->belongsTo(User::class, 'shortlisted_user_id');
    }
}
