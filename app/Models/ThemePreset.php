<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ThemePreset extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
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
