<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactUnlock extends Model
{
    protected $fillable = [
        'user_id',
        'unlocked_user_id',
        'amount_paid',
        'payment_method'
    ];

    protected $casts = [
        'amount_paid' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function unlockedUser()
    {
        return $this->belongsTo(User::class, 'unlocked_user_id');
    }
}
