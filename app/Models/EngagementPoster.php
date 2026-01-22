<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class EngagementPoster extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'poster_image',
        'engagement_date',
        'announcement_title',
        'announcement_message',
        'is_active',
        'is_verified',
        'display_expire_at',
    ];

    protected $casts = [
        'engagement_date' => 'date',
        'display_expire_at' => 'datetime',
        'is_active' => 'boolean',
        'is_verified' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
