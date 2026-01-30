<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromotionSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'views_required',
        'likes_required',
        'comments_required',
        'payout_amount',
        'is_likes_enabled',
        'is_comments_enabled',
        'payout_period_days',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'is_likes_enabled' => 'boolean',
        'is_comments_enabled' => 'boolean',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'payout_amount' => 'decimal:2',
    ];
}
