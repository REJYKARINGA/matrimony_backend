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
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('duration_days');
            $table->decimal('price', 10, 2);
            $table->integer('max_messages')->nullable(); // null = unlimited
            $table->integer('max_contacts')->nullable(); // null = unlimited
            $table->boolean('can_view_contact')->default(false);
            $table->boolean('priority_listing')->default(false);
            $table->text('features')->nullable(); // JSON array of features
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
