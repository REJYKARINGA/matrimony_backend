<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('admin_settings', function (Blueprint $table) {
            $table->string('theme_primary_color', 20)->default('#00C897')->after('review_max_prompts');
            $table->string('theme_secondary_color', 20)->default('#00A87D')->after('theme_primary_color');
            $table->string('theme_background_color', 20)->default('#F5FBF9')->after('theme_secondary_color');
            $table->string('theme_surface_color', 20)->default('#FFFFFF')->after('theme_background_color');
            $table->string('theme_text_color', 20)->default('#212121')->after('theme_surface_color');
            $table->string('theme_gradient_start', 20)->default('#00C897')->after('theme_text_color');
            $table->string('theme_gradient_end', 20)->default('#00A87D')->after('theme_gradient_start');
            $table->string('theme_dark_primary', 20)->default('#42A5F5')->after('theme_gradient_end');
            $table->string('theme_dark_secondary', 20)->default('#64B5F6')->after('theme_dark_primary');
        });
    }

    public function down(): void
    {
        Schema::table('admin_settings', function (Blueprint $table) {
            $table->dropColumn([
                'theme_primary_color',
                'theme_secondary_color',
                'theme_background_color',
                'theme_surface_color',
                'theme_text_color',
                'theme_gradient_start',
                'theme_gradient_end',
                'theme_dark_primary',
                'theme_dark_secondary',
            ]);
        });
    }
};
