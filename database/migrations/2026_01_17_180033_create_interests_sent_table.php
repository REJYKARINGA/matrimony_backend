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
        Schema::create('interests_sent', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sender_id');
            $table->foreign('sender_id')->references('id')->on('users');
            $table->unsignedBigInteger('receiver_id');
            $table->foreign('receiver_id')->references('id')->on('users');
            $table->string('status')->default('pending'); // pending, accepted, rejected, withdrawn
            $table->text('message')->nullable();
            $table->timestamp('sent_at')->useCurrent();
            $table->timestamp('responded_at')->nullable();
        });

        // Create unique index for sender_id and receiver_id
        Schema::table('interests_sent', function (Blueprint $table) {
            $table->unique(['sender_id', 'receiver_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interests_sent');
    }
};
