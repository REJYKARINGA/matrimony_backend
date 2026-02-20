<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Each user (especially mediators) gets their own unique reference code
            // 6 uppercase English letters, e.g. ABCXYZ — unique per user/mediator
            $table->string('reference_code', 6)->unique()->nullable()->after('matrimony_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('reference_code');
        });
    }
};
