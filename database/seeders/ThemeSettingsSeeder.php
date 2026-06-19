<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AdminSetting;

class ThemeSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $setting = AdminSetting::first();

        $themeData = [
            'theme_primary_color' => '#00C897',
            'theme_secondary_color' => '#00A87D',
            'theme_background_color' => '#F5FBF9',
            'theme_surface_color' => '#FFFFFF',
            'theme_text_color' => '#212121',
            'theme_gradient_start' => '#00C897',
            'theme_gradient_end' => '#00A87D',
            'theme_dark_primary' => '#42A5F5',
            'theme_dark_secondary' => '#64B5F6',
        ];

        if ($setting) {
            $setting->update($themeData);
            $this->command->info('Theme settings updated successfully.');
        } else {
            AdminSetting::create($themeData);
            $this->command->info('Admin settings created with theme colors.');
        }
    }
}
