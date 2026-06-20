<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_menu_permission', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('menu_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['role_id', 'menu_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_menu_permission');
    }
};
