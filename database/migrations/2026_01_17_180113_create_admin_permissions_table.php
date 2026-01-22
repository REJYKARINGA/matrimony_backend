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
        Schema::create('admin_permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users');
            $table->string('permission'); // manage_users, verify_profiles, handle_reports, etc
            $table->unsignedBigInteger('granted_by')->nullable(); // admin user_id who granted permission
            $table->foreign('granted_by')->references('id')->on('users');
            $table->timestamp('granted_at')->useCurrent();
        });

        // Create unique index for user_id and permission
        Schema::table('admin_permissions', function (Blueprint $table) {
            $table->unique(['user_id', 'permission']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_permissions');
    }
};
