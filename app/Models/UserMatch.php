<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserMatch extends Model
{
    protected $table = 'matches';
    protected $fillable = [
        'user1_id',
        'user2_id',
        'match_score',
        'status',
    ];

    protected $casts = [
        'match_score' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
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
}
