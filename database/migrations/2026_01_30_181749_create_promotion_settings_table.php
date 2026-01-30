<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('promotion_settings', function (Blueprint $table) {
            $table->id();
            $table->integer('views_required');
            $table->integer('likes_required')->default(0);
            $table->integer('comments_required')->default(0);
            $table->decimal('payout_amount', 10, 2);
            $table->boolean('is_likes_enabled')->default(false);
            $table->boolean('is_comments_enabled')->default(false);
            $table->integer('payout_period_days')->default(7);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotion_settings');
    }
};
