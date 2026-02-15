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
            $table->unsignedBigInteger('religion_id')->nullable()->after('marital_status');
            $table->json('caste_ids')->nullable()->after('religion_id');
            $table->json('education_ids')->nullable()->after('caste_ids');
            $table->json('occupation_ids')->nullable()->after('education_ids');

            $table->dropColumn(['religion', 'caste', 'education', 'occupation']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('preferences', function (Blueprint $table) {
            $table->string('religion')->nullable();
            $table->json('caste')->nullable();
            $table->json('education')->nullable();
            $table->json('occupation')->nullable();

            $table->dropColumn(['religion_id', 'caste_ids', 'education_ids', 'occupation_ids']);
        });
    }
};
