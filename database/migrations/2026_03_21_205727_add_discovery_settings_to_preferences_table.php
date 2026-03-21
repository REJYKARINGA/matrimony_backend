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
        Schema::table('preferences', function (Blueprint $table) {
            $table->boolean('hide_viewed')->default(true)->after('max_distance');
            $table->boolean('hide_interested')->default(true)->after('hide_viewed');
            $table->string('sort_by')->default('recent_login')->after('hide_interested');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('preferences', function (Blueprint $table) {
            $table->dropColumn(['hide_viewed', 'hide_interested', 'sort_by']);
        });
    }
};
