<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('user_interests', function (Blueprint $table) {
            $table->dropColumn(['proficiency_level', 'years_of_experience', 'is_primary']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_interests', function (Blueprint $table) {
            $table->string('proficiency_level', 20)->default('intermediate');
            $table->integer('years_of_experience')->nullable();
            $table->boolean('is_primary')->default(false);
        });
    }
};
