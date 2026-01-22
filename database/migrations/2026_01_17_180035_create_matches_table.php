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
        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user1_id');
            $table->foreign('user1_id')->references('id')->on('users');
            $table->unsignedBigInteger('user2_id');
            $table->foreign('user2_id')->references('id')->on('users');
            $table->decimal('match_score', 5, 2)->nullable(); // Algorithm calculated score
            $table->string('status')->default('suggested'); // suggested, contacted, in_progress, matched, rejected
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
        });

        // Create unique index for user1_id and user2_id
        Schema::table('matches', function (Blueprint $table) {
            $table->unique(['user1_id', 'user2_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('matches');
    }
};
