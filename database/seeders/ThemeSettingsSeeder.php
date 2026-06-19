<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ThemeSetting;

class ThemeSettingsSeeder extends Seeder
{
    public function run(): void
    {
        ThemeSetting::truncate();

        ThemeSetting::create([
            'primary_color' => '#00C897',
            'secondary_color' => '#00A87D',
            'background_color' => '#F5FBF9',
            'surface_color' => '#FFFFFF',
            'text_color' => '#212121',
            'gradient_start' => '#00C897',
            'gradient_end' => '#00A87D',
            'dark_primary' => '#42A5F5',
            'dark_secondary' => '#64B5F6',
        ]);

        $this->command->info('Theme settings seeded successfully!');
    }
}
