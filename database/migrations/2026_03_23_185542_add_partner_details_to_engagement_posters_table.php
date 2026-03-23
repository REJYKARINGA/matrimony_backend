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
        Schema::table('engagement_posters', function (Blueprint $table) {
            $table->string('partner_matrimony_id')->nullable();
            $table->enum('partner_status', ['pending', 'confirmed', 'rejected'])->default('pending');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('engagement_posters', function (Blueprint $table) {
            $table->dropColumn(['partner_matrimony_id', 'partner_status']);
        });
    }
};
