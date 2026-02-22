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
        Schema::create('user_interests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('interest_id')->constrained('interests_hobbies')->onDelete('cascade');
            $table->string('proficiency_level', 20)->default('intermediate')->comment('beginner, intermediate, advanced, expert');
            $table->integer('years_of_experience')->nullable();
            $table->boolean('is_primary')->default(false)->comment('marks top 3 primary interests');
            $table->timestamps();

            $table->unique(['user_id', 'interest_id']);
            $table->index('user_id');
            $table->index('interest_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_interests');
    }
};
