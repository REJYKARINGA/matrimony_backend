<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use App\Models\SubscriptionPlan;
use App\Models\Payment;

class UserSubscription extends Model
{
    protected $table = 'user_subscriptions';
    protected $fillable = [
        'user_id',
        'plan_id',
        'start_date',
        'end_date',
        'status',
        'payment_id',
        'amount_paid',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'amount_paid' => 'decimal:2',
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
     * Relationship with subscription plan
     */
    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    /**
     * Relationship with payment
     */
    public function payment()
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }
}
