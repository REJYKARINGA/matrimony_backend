<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ThemeSetting extends Model
{
    use HasFactory;

    protected $table = 'theme_settings';

    protected $fillable = [
        'primary_color',
        'secondary_color',
        'background_color',
        'surface_color',
        'text_color',
        'gradient_start',
        'gradient_end',
        'dark_primary',
        'dark_secondary',
    ];
}
