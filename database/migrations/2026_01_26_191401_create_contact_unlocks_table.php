<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('contact_unlocks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('unlocked_user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('unlocked_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->decimal('amount_paid', 10, 2)->default(49.00);
            $table->enum('payment_method', ['wallet', 'direct'])->default('direct');
            $table->timestamps();

            $table->unique(['user_id', 'unlocked_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_unlocks');
    }
};
