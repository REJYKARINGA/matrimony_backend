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
        // Add the new password column
        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->after('phone');
        });

        // Copy data from password_hash to password
        \DB::statement('UPDATE users SET password = password_hash');

        // Drop the old password_hash column
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('password_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add back the password_hash column
        Schema::table('users', function (Blueprint $table) {
            $table->string('password_hash')->after('phone');
        });

        // Copy data from password to password_hash
        \DB::statement('UPDATE users SET password_hash = password');

        // Drop the password column
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('password');
        });
    }
};
