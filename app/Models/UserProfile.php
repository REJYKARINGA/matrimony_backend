<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class UserProfile extends Model
{
    protected $table = 'user_profiles';
    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'date_of_birth',
        'gender',
        'height',
        'weight',
        'drug_addiction',
        'smoke',
        'alcohol',
        'marital_status',
        'religion',
        'caste',
        'sub_caste',
        'mother_tongue',
        'profile_picture',
        'bio',
        'education',
        'occupation',
        'annual_income',
        'city',
        'district',
        'county',
        'state',
        'country',
        'postal_code',
        'latitude',
        'longitude',
        'location_updated_at',
        'is_active_verified',
        'changed_fields',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'height' => 'integer',
        'weight' => 'integer',
        'drug_addiction' => 'boolean',
        'annual_income' => 'decimal:2',
        'latitude' => 'float',
        'longitude' => 'float',
        'location_updated_at' => 'datetime',
        'is_active_verified' => 'boolean',
        'changed_fields' => 'array',
    ];

    /**
     * Relationship with user
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
