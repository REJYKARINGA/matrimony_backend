<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class Preference extends Model
{
    protected $table = 'preferences';
    protected $fillable = [
        'user_id',
        'min_age',
        'max_age',
        'min_height',
        'max_height',
        'marital_status',
        'religion',
        'caste',
        'education',
        'occupation',
        'min_income',
        'max_income',
        'preferred_locations',
    ];

    protected $casts = [
        'min_age' => 'integer',
        'max_age' => 'integer',
        'min_height' => 'integer',
        'max_height' => 'integer',
        'min_income' => 'decimal:2',
        'max_income' => 'decimal:2',
        'preferred_locations' => 'array', // JSON array
    ];

    /**
     * Relationship with user
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
