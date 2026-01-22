<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use App\Models\UserSubscription;

class Payment extends Model
{
    protected $table = 'payments';
    protected $fillable = [
        'user_id',
        'subscription_id',
        'amount',
        'payment_method',
        'transaction_id',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'datetime',
        'created_at' => 'datetime',
    ];

    /**
     * Relationship with user
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relationship with user subscription
     */
    public function userSubscription()
    {
        return $this->belongsTo(UserSubscription::class, 'subscription_id');
    }
}
