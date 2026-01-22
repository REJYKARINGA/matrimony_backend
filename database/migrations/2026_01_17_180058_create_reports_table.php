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
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('reporter_id');
            $table->foreign('reporter_id')->references('id')->on('users');
            $table->unsignedBigInteger('reported_user_id');
            $table->foreign('reported_user_id')->references('id')->on('users');
            $table->string('reason');
            $table->text('description')->nullable();
            $table->string('status')->default('pending'); // pending, reviewing, resolved, dismissed
            $table->unsignedBigInteger('reviewed_by')->nullable(); // staff/admin user_id
            $table->foreign('reviewed_by')->references('id')->on('users');
            $table->text('resolution_notes')->nullable();
            $table->timestamp('reported_at')->useCurrent();
            $table->timestamp('reviewed_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
