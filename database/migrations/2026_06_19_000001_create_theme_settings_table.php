<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('theme_settings', function (Blueprint $table) {
            $table->id();
            $table->string('primary_color', 20)->default('#00C897');
            $table->string('secondary_color', 20)->default('#00A87D');
            $table->string('background_color', 20)->default('#F5FBF9');
            $table->string('surface_color', 20)->default('#FFFFFF');
            $table->string('text_color', 20)->default('#212121');
            $table->string('gradient_start', 20)->default('#00C897');
            $table->string('gradient_end', 20)->default('#00A87D');
            $table->string('dark_primary', 20)->default('#42A5F5');
            $table->string('dark_secondary', 20)->default('#64B5F6');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('theme_settings');
    }
};
