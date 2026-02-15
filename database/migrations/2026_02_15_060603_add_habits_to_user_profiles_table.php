<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->boolean('drug_addiction')->default(false)->after('weight');
            $table->enum('smoke', ['never', 'occasionally', 'regularly'])->default('never')->after('drug_addiction');
            $table->enum('alcohol', ['never', 'occasionally', 'regularly'])->default('never')->after('smoke');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropColumn(['drug_addiction', 'smoke', 'alcohol']);
        });
    }
};
