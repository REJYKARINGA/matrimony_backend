<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Religion extends Model
{
    protected $fillable = ['name', 'is_active', 'order_number'];

    protected $casts = [
        'is_active' => 'boolean',
        'order_number' => 'integer',
    ];

    public function castes()
    {
        return $this->hasMany(Caste::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order_number', 'asc');
    }
}
