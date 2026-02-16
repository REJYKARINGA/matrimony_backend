<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use App\Models\Religion;
use App\Models\Caste;
use App\Models\SubCaste;
use App\Models\Education;
use App\Models\Occupation;

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
        'sub_caste_ids',
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
        'sub_caste_ids' => 'array',
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

    public function religion()
    {
        return $this->belongsTo(Religion::class, 'religion_id');
    }

    protected $appends = ['religion_name', 'caste_names', 'sub_caste_names', 'education_names', 'occupation_names'];

    public function getReligionNameAttribute()
    {
        return $this->religion?->name;
    }

    public function getCasteNamesAttribute()
    {
        if (empty($this->caste_ids))
            return [];
        return Caste::whereIn('id', $this->caste_ids)->pluck('name')->toArray();
    }

    public function getSubCasteNamesAttribute()
    {
        if (empty($this->sub_caste_ids))
            return [];
        return SubCaste::whereIn('id', $this->sub_caste_ids)->pluck('name')->toArray();
    }

    public function getEducationNamesAttribute()
    {
        if (empty($this->education_ids))
            return [];
        return Education::whereIn('id', $this->education_ids)->pluck('name')->toArray();
    }

    public function getOccupationNamesAttribute()
    {
        if (empty($this->occupation_ids))
            return [];
        return Occupation::whereIn('id', $this->occupation_ids)->pluck('name')->toArray();
    }
}
