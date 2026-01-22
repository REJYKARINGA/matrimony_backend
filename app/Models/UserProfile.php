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
        'state',
        'country',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'height' => 'integer',
        'weight' => 'integer',
        'annual_income' => 'decimal:2',
    ];

    /**
     * Relationship with user
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
