<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Suggestion extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'status',
        'response_text',
        'response_photo',
        'responded_at',
        'responded_by',
        'user_photos',
    ];

    protected $casts = [
        'user_photos' => 'array',
        'responded_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function responder()
    {
        return $this->belongsTo(User::class, 'responded_by');
    }
}
