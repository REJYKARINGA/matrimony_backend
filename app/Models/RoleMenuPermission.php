<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoleMenuPermission extends Model
{
    protected $table = 'role_menu_permission';

    protected $fillable = [
        'role_id',
        'menu_id',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function menu()
    {
        return $this->belongsTo(Menu::class);
    }
}
