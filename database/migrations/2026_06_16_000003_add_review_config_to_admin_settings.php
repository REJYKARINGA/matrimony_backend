<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_settings', function (Blueprint $table) {
            $table->boolean('review_enabled')->default(true)->after('wallet_in_maintenance_android');
            $table->integer('review_unlock_threshold')->default(10)->after('review_enabled');
            $table->integer('review_min_days_between')->default(90)->after('review_unlock_threshold');
            $table->integer('review_max_prompts')->default(3)->after('review_min_days_between');
        });
    }

    public function down(): void
    {
        Schema::table('admin_settings', function (Blueprint $table) {
            $table->dropColumn(['review_enabled', 'review_unlock_threshold', 'review_min_days_between', 'review_max_prompts']);
        });
    }
};
