<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InterestHobby extends Model
{
    use HasFactory;

    protected $table = 'interests_hobbies';

    protected $fillable = [
        'interest_name',
        'interest_type',
        'trending_number',
        'is_active',
    ];

    protected $casts = [
        'trending_number' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Scope for active interests
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for trending interests (lower number = more trending)
     */
    public function scopeTrending($query, $limit = 10)
    {
        return $query->orderBy('trending_number', 'asc')->limit($limit);
    }

    /**
     * Scope for interest type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('interest_type', $type);
    }
}
