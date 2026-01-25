<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class Notification extends Model
{
    protected $table = 'notifications';
    public $timestamps = false;
    protected $fillable = [
        'user_id',
        'sender_id',
        'type',
        'title',
        'message',
        'reference_id',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'created_at' => 'datetime',
        'read_at' => 'datetime',
    ];

    /**
     * Relationship with user (receiver)
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relationship with sender
     */
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
