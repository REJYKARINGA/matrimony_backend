<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserPreferredCity extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'latitude',
        'longitude',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
