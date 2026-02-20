<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * references table tracks:
     *  - referenced_by_id  → the mediator who shared their reference code
     *  - referenced_user_id → the user who used that code at registration
     *  - reference_code     → snapshot of the code used (in case code is changed later)
     *  - reference_type     → e.g. 'mediator', 'agent', 'staff', etc.
     *  - purchased_count    → incremented every time referenced_user unlocks a contact
     */
    public function up(): void
    {
        Schema::create('references', function (Blueprint $table) {
            $table->id();

            // Who gave the reference (the mediator / referrer)
            $table->unsignedBigInteger('referenced_by_id');
            $table->foreign('referenced_by_id')
                ->references('id')->on('users')
                ->onDelete('cascade');

            // Who was referred (the new user who used the code)
            $table->unsignedBigInteger('referenced_user_id');
            $table->foreign('referenced_user_id')
                ->references('id')->on('users')
                ->onDelete('cascade');

            // Snapshot of the 6-letter code used at registration time
            $table->string('reference_code', 6);

            // Type of reference — flexible for future roles (mediator, agent, etc.)
            $table->string('reference_type')->default('mediator');

            // Increases every time the referred user unlocks a contact (purchases)
            $table->unsignedInteger('purchased_count')->default(0);

            // Soft deletes + timestamps
            $table->timestamps();
            $table->softDeletes();

            // A user can only be referred once (one active reference record per user)
            $table->unique('referenced_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('references');
    }
};
