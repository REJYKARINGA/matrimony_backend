<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = [
        'name',
        'label',
        'description',
        'sort_order',
    ];

    public function menus()
    {
        return $this->belongsToMany(Menu::class, 'role_menu_permission')
            ->withTimestamps();
    }
}
