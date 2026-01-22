<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PasswordResetToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'token',
        'expires_at',
        'used_at',
        'verified_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    /**
     * Check if the token has expired
     */
    public function isExpired()
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Scope to get a valid token
     */
    public function scopeValid($query)
    {
        return $query->whereNull('used_at')
                     ->where('expires_at', '>', now());
    }
}