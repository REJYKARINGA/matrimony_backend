<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class InterestSent extends Model
{
    protected $table = 'interests_sent';
    protected $fillable = [
        'sender_id',
        'receiver_id',
        'status',
        'message',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'responded_at' => 'datetime',
    ];

    /**
     * Relationship with sender (user)
     */
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Relationship with receiver (user)
     */
    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }
}
