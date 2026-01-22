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
        // Check if the table exists and has the expected columns
        if (Schema::hasTable('password_reset_tokens')) {
            // Check if the additional columns already exist
            $columns = Schema::getColumnListing('password_reset_tokens');

            // Add columns if they don't exist rather than dropping and recreating
            if (!in_array('used_at', $columns)) {
                Schema::table('password_reset_tokens', function (Blueprint $table) {
                    $table->timestamp('used_at')->nullable();
                });
            }

            if (!in_array('verified_at', $columns)) {
                Schema::table('password_reset_tokens', function (Blueprint $table) {
                    $table->timestamp('verified_at')->nullable();
                });
            }
        } else {
            // If the table doesn't exist, create it with all required columns
            Schema::create('password_reset_tokens', function (Blueprint $table) {
                $table->id();
                $table->string('email')->index();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamp('used_at')->nullable();
                $table->timestamp('verified_at')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('password_reset_tokens');

        // Recreate the original table structure
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }
};