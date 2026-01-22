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
        Schema::create('shortlisted_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users');
            $table->unsignedBigInteger('shortlisted_user_id');
            $table->foreign('shortlisted_user_id')->references('id')->on('users');
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        // Create unique index for user_id and shortlisted_user_id
        Schema::table('shortlisted_profiles', function (Blueprint $table) {
            $table->unique(['user_id', 'shortlisted_user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shortlisted_profiles');
    }
};
