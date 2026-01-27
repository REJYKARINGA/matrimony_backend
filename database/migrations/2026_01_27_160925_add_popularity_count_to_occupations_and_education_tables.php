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
        Schema::table('occupations', function (Blueprint $table) {
            $table->unsignedBigInteger('popularity_count')->default(0)->after('order_number');
        });

        Schema::table('education', function (Blueprint $table) {
            $table->unsignedBigInteger('popularity_count')->default(0)->after('order_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('occupations', function (Blueprint $table) {
            $table->dropColumn('popularity_count');
        });

        Schema::table('education', function (Blueprint $table) {
            $table->dropColumn('popularity_count');
        });
    }
};
