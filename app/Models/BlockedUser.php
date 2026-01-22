<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class BlockedUser extends Model
{
    protected $table = 'blocked_users';
    protected $fillable = [
        'user_id',
        'blocked_user_id',
        'reason',
    ];

    protected $casts = [
        'blocked_at' => 'datetime',
    ];

    /**
     * Relationship with user
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relationship with blocked user
     */
    public function blockedUser()
    {
        return $this->belongsTo(User::class, 'blocked_user_id');
    }
}
