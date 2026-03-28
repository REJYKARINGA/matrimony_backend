<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WalletTransferOtp extends Model
{
    protected $fillable = [
        'sender_id',
        'recipient_id',
        'amount',
        'otp',
        'expires_at',
        'verified_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipient()
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function scopeValid($query)
    {
        return $query->where('expires_at', '>', now())
                     ->whereNull('verified_at');
    }
}
