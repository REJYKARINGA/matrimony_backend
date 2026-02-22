<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Personality extends Model
{
    use HasFactory;

    protected $table = 'personalities';

    protected $fillable = [
        'personality_name',
        'personality_type',
        'trending_number',
        'is_active',
    ];

    protected $casts = [
        'trending_number' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get users with this personality
     */
    public function users(): HasMany
    {
        return $this->hasMany(UserPersonality::class, 'personality_id');
    }

    /**
     * Scope for active personalities
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for trending personalities
     */
    public function scopeTrending($query, $limit = 10)
    {
        return $query->orderBy('trending_number', 'asc')->limit($limit);
    }

    /**
     * Scope for personality type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('personality_type', $type);
    }
}
