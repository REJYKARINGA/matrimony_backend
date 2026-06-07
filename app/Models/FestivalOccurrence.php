<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FestivalOccurrence extends Model
{
    protected $fillable = [
        'festival_id',
        'year',
        'start_at',
        'end_at',
        'resolved_from',
    ];

    protected $casts = [
        'year' => 'integer',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    public function festival()
    {
        return $this->belongsTo(Festival::class);
    }

    public function scopeActive($query)
    {
        return $query->where('start_at', '<=', now())
            ->where('end_at', '>=', now());
    }
}
