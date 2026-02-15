<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Caste extends Model
{
    protected $fillable = ['religion_id', 'name', 'is_active', 'order_number'];

    protected $casts = [
        'is_active' => 'boolean',
        'order_number' => 'integer',
        'religion_id' => 'integer',
    ];

    public function religion()
    {
        return $this->belongsTo(Religion::class);
    }

    public function subCastes()
    {
        return $this->hasMany(SubCaste::class);
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
