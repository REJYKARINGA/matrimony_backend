<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_settings', function (Blueprint $table) {
            $table->boolean('wallet_is_active')->default(true)->after('free_unlock_expires_at');
            $table->boolean('wallet_in_maintenance_ios')->default(false)->after('wallet_is_active');
            $table->boolean('wallet_in_maintenance_android')->default(false)->after('wallet_in_maintenance_ios');
        });
    }

    public function down(): void
    {
        Schema::table('admin_settings', function (Blueprint $table) {
            $table->dropColumn(['wallet_is_active', 'wallet_in_maintenance_ios', 'wallet_in_maintenance_android']);
        });
    }
};
