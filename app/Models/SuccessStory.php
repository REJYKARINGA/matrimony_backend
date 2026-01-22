<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class SuccessStory extends Model
{
    protected $table = 'success_stories';
    protected $fillable = [
        'user1_id',
        'user2_id',
        'title',
        'story',
        'wedding_date',
        'photo_url',
        'is_approved',
        'approved_by',
        'is_featured',
    ];

    protected $casts = [
        'wedding_date' => 'date',
        'is_approved' => 'boolean',
        'is_featured' => 'boolean',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    /**
     * Relationship with user1
     */
    public function user1()
    {
        return $this->belongsTo(User::class, 'user1_id');
    }

    /**
     * Relationship with user2
     */
    public function user2()
    {
        return $this->belongsTo(User::class, 'user2_id');
    }

    /**
     * Relationship with approver (user)
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
