<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyTopPick extends Model
{
    protected $fillable = [
        'user_id',
        'picked_user_id',
        'picked_date'
    ];

    /**
     * Get the user who received the pick.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the profile that was picked.
     */
    public function pickedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'picked_user_id');
    }
}
