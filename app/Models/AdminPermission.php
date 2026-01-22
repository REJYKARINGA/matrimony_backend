<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class AdminPermission extends Model
{
    protected $table = 'admin_permissions';
    protected $fillable = [
        'user_id',
        'permission',
        'granted_by',
    ];

    protected $casts = [
        'granted_at' => 'datetime',
    ];

    /**
     * Relationship with user
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relationship with granter (user)
     */
    public function granter()
    {
        return $this->belongsTo(User::class, 'granted_by');
    }
}
