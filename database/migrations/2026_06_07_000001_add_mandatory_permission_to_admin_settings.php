<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('admin_settings', function (Blueprint $table) {
            $table->boolean('mandatory_permission_for_unlock')->default(false)->after('user_contact_permission_unlock');
        });
    }

    public function down(): void
    {
        Schema::table('admin_settings', function (Blueprint $table) {
            $table->dropColumn('mandatory_permission_for_unlock');
        });
    }
};
