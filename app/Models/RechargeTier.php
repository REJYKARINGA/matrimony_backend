<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RechargeTier extends Model
{
    use HasFactory;

    protected $fillable = [
        'amount',
        'contacts',
        'priority_order',
        'is_active',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'contacts' => 'integer',
        'priority_order' => 'integer',
        'is_active' => 'boolean',
    ];
}
