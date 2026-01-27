<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Education extends Model
{
    protected $table = 'education';

    protected $fillable = [
        'name',
        'is_active',
        'order_number',
        'popularity_count',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order_number' => 'integer',
        'popularity_count' => 'integer',
    ];

    /**
     * Scope to get only active education options
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order by order_number
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('popularity_count', 'desc')
            ->orderBy('order_number', 'asc');
    }
}
