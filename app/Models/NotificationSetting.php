<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationSetting extends Model
{
    protected $fillable = [
        'user_id',
        'notify_matches',
        'notify_messages',
        'notify_profile_views',
        'notify_interests',
        'notify_email',
        'notify_push',
    ];

    protected $casts = [
        'notify_matches' => 'boolean',
        'notify_messages' => 'boolean',
        'notify_profile_views' => 'boolean',
        'notify_interests' => 'boolean',
        'notify_email' => 'boolean',
        'notify_push' => 'boolean',
    ];

    /**
     * Relationship with user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
