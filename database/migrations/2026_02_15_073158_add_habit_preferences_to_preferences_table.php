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
        Schema::table('preferences', function (Blueprint $table) {
            $table->string('drug_addiction')->default('any')->after('preferred_locations');
            $table->json('smoke')->nullable()->after('drug_addiction');
            $table->json('alcohol')->nullable()->after('smoke');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('preferences', function (Blueprint $table) {
            $table->dropColumn(['drug_addiction', 'smoke', 'alcohol']);
        });
    }
};
