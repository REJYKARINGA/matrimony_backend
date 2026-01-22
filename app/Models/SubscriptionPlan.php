<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\UserSubscription;

class SubscriptionPlan extends Model
{
    protected $table = 'subscription_plans';
    protected $fillable = [
        'name',
        'duration_days',
        'price',
        'max_messages',
        'max_contacts',
        'can_view_contact',
        'priority_listing',
        'features',
        'is_active',
    ];

    protected $casts = [
        'duration_days' => 'integer',
        'price' => 'decimal:2',
        'max_messages' => 'integer',
        'max_contacts' => 'integer',
        'can_view_contact' => 'boolean',
        'priority_listing' => 'boolean',
        'is_active' => 'boolean',
        'features' => 'array', // JSON array
        'created_at' => 'datetime',
    ];

    /**
     * Relationship with user subscriptions
     */
    public function userSubscriptions()
    {
        return $this->hasMany(UserSubscription::class, 'plan_id');
    }
}
