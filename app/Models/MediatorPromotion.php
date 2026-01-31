<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\PromotionSetting;

class MediatorPromotion extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'promotion_setting_id',
        'platform',
        'link',
        'username',
        'views_count',
        'likes_count',
        'comments_count',
        'status',
        'calculated_payout',
        'total_paid_amount',
        'paid_at',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'calculated_payout' => 'decimal:2',
        'total_paid_amount' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function setting()
    {
        return $this->belongsTo(PromotionSetting::class, 'promotion_setting_id');
    }
}
