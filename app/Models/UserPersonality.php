<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPersonality extends Model
{
    use HasFactory;

    protected $table = 'user_personality';

    protected $fillable = [
        'user_id',
        'personality_id',
    ];

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the personality details
     */
    public function personality(): BelongsTo
    {
        return $this->belongsTo(Personality::class, 'personality_id');
    }
}
