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
        Schema::create('profile_photos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('photo_url');
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_verified')->default(false);
            $table->unsignedBigInteger('verified_by')->nullable(); // staff/admin user_id
            $table->foreign('verified_by')->references('id')->on('users');
            $table->timestamp('upload_date')->useCurrent();
            $table->timestamp('verification_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profile_photos');
    }
};
