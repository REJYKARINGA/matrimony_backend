<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class Report extends Model
{
    protected $table = 'reports';
    protected $fillable = [
        'reporter_id',
        'reported_user_id',
        'reason',
        'description',
        'status',
        'reviewed_by',
        'resolution_notes',
    ];

    protected $casts = [
        'reported_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    /**
     * Relationship with reporter (user)
     */
    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    /**
     * Relationship with reported user
     */
    public function reportedUser()
    {
        return $this->belongsTo(User::class, 'reported_user_id');
    }

    /**
     * Relationship with reviewer (user)
     */
    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
