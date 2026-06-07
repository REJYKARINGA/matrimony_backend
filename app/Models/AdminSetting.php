<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'daily_contact_unlock_limit',
        'user_contact_permission_unlock',
        'mandatory_permission_for_unlock',
    ];

    protected $casts = [
        'daily_contact_unlock_limit' => 'integer',
        'user_contact_permission_unlock' => 'boolean',
        'mandatory_permission_for_unlock' => 'boolean',
    ];
}
