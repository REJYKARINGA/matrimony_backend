<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserInterest extends Model
{
    use HasFactory;

    protected $table = 'user_interests';

    protected $fillable = [
        'user_id',
        'interest_id',
    ];

    /**
     * Get the user that owns this interest
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the interest/hobby details
     */
    public function interest(): BelongsTo
    {
        return $this->belongsTo(InterestHobby::class, 'interest_id');
    }
}
