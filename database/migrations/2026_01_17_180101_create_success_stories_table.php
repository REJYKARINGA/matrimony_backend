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
        Schema::create('success_stories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user1_id');
            $table->foreign('user1_id')->references('id')->on('users');
            $table->unsignedBigInteger('user2_id');
            $table->foreign('user2_id')->references('id')->on('users');
            $table->string('title')->nullable();
            $table->text('story')->nullable();
            $table->date('wedding_date')->nullable();
            $table->string('photo_url')->nullable();
            $table->boolean('is_approved')->default(false);
            $table->unsignedBigInteger('approved_by')->nullable(); // admin user_id
            $table->foreign('approved_by')->references('id')->on('users');
            $table->boolean('is_featured')->default(false);
            $table->timestamp('submitted_at')->useCurrent();
            $table->timestamp('approved_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('success_stories');
    }
};
