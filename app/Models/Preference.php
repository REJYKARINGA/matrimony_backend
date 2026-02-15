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
        'religion_id',
        'caste_ids',
        'education_ids',
        'occupation_ids',
        'min_income',
        'max_income',
        'max_distance',
        'preferred_locations',
        'drug_addiction',
        'smoke',
        'alcohol',
    ];

    protected $casts = [
        'min_age' => 'integer',
        'max_age' => 'integer',
        'min_height' => 'integer',
        'max_height' => 'integer',
        'min_income' => 'decimal:2',
        'max_income' => 'decimal:2',
        'max_distance' => 'integer',
        'religion_id' => 'integer',
        'caste_ids' => 'array',
        'education_ids' => 'array',
        'occupation_ids' => 'array',
        'preferred_locations' => 'array',
        'smoke' => 'array',
        'alcohol' => 'array',
    ];

    /**
     * Relationship with user
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
