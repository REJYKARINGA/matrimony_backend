<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    protected $fillable = [
        'path',
        'label',
        'group',
        'icon',
        'sort_order',
    ];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_menu_permission')
            ->withTimestamps();
    }
}
