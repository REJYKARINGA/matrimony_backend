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
        'religion_id',
        'caste_id',
        'sub_caste_id',
        'mother_tongue',
        'profile_picture',
        'bio',
        'education_id',
        'occupation_id',
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
        'religion_id' => 'integer',
        'caste_id' => 'integer',
        'sub_caste_id' => 'integer',
        'education_id' => 'integer',
        'occupation_id' => 'integer',
    ];

    /**
     * Relationship with user
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function religionModel()
    {
        return $this->belongsTo(Religion::class, 'religion_id');
    }

    public function casteModel()
    {
        return $this->belongsTo(Caste::class, 'caste_id');
    }

    public function subCasteModel()
    {
        return $this->belongsTo(SubCaste::class, 'sub_caste_id');
    }

    public function educationModel()
    {
        return $this->belongsTo(Education::class, 'education_id');
    }

    public function occupationModel()
    {
        return $this->belongsTo(Occupation::class, 'occupation_id');
    }
}
