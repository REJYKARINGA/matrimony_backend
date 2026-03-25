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
        Schema::table('profile_photos', function (Blueprint $table) {
            $table->boolean('is_rejected')->default(false)->after('is_verified');
            $table->string('rejection_reason')->nullable()->after('is_rejected');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('profile_photos', function (Blueprint $table) {
            $table->dropColumn(['is_rejected', 'rejection_reason']);
        });
    }
};
