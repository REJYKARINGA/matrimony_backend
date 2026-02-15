<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubCaste extends Model
{
    protected $fillable = ['caste_id', 'name', 'is_active', 'order_number'];

    protected $casts = [
        'is_active' => 'boolean',
        'order_number' => 'integer',
        'caste_id' => 'integer',
    ];

    public function caste()
    {
        return $this->belongsTo(Caste::class);
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
