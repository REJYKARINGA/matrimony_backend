<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('admin_settings', function (Blueprint $table) {
            $table->boolean('free_unlock_enabled')->default(false)->after('mandatory_permission_for_unlock');
            $table->timestamp('free_unlock_expires_at')->nullable()->after('free_unlock_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('admin_settings', function (Blueprint $table) {
            $table->dropColumn(['free_unlock_enabled', 'free_unlock_expires_at']);
        });
    }
};
