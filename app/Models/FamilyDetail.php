<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class FamilyDetail extends Model
{
    protected $table = 'family_details';
    protected $fillable = [
        'user_id',
        'father_name',
        'father_occupation',
        'mother_name',
        'mother_occupation',
        'siblings',
        'family_type',
        'family_status',
        'family_location',
        'elder_sister',
        'elder_brother',
        'younger_sister',
        'younger_brother',
        'twin_type',
        'father_alive',
        'mother_alive',
        'is_disabled',
        'guardian',
        'show',
    ];

    protected $casts = [
        'siblings' => 'integer',
        'elder_sister' => 'integer',
        'elder_brother' => 'integer',
        'younger_sister' => 'integer',
        'younger_brother' => 'integer',
        'father_alive' => 'boolean',
        'mother_alive' => 'boolean',
        'is_disabled' => 'boolean',
        'show' => 'boolean',
    ];

    /**
     * Relationship with user
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
