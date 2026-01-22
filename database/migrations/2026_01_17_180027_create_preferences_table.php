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
        Schema::create('preferences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->integer('min_age')->nullable();
            $table->integer('max_age')->nullable();
            $table->integer('min_height')->nullable();
            $table->integer('max_height')->nullable();
            $table->string('marital_status')->nullable();
            $table->string('religion')->nullable();
            $table->string('caste')->nullable();
            $table->string('education')->nullable();
            $table->string('occupation')->nullable();
            $table->decimal('min_income', 10, 2)->nullable();
            $table->decimal('max_income', 10, 2)->nullable();
            $table->text('preferred_locations')->nullable(); // JSON array of cities
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('preferences');
    }
};
