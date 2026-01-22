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
        // Check if username column exists and drop it
        if (Schema::hasColumn('users', 'username')) {
            // Drop the unique index first using raw SQL for better SQLite compatibility
            try {
                \DB::statement('DROP INDEX IF EXISTS users_username_unique');
            } catch (\Exception $e) {
                // If the index doesn't exist or can't be dropped separately, continue
            }

            // Now drop the column
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('username');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
