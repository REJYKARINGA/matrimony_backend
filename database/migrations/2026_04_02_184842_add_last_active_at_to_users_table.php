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
        // Safe check: Only add if column doesn't already exist
        if (!Schema::hasColumn('users', 'last_active_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('last_active_at')->nullable()->after('last_login');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('users', 'last_active_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('last_active_at');
            });
        }
    }
};
