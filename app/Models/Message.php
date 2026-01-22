<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class Message extends Model
{
    protected $table = 'messages';
    protected $fillable = [
        'sender_id',
        'receiver_id',
        'message',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'sent_at' => 'datetime',
        'read_at' => 'datetime',
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
