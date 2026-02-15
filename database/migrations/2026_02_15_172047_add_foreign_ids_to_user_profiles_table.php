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
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->unsignedBigInteger('religion_id')->nullable()->after('religion');
            $table->unsignedBigInteger('caste_id')->nullable()->after('caste');
            $table->unsignedBigInteger('sub_caste_id')->nullable()->after('sub_caste');
            $table->unsignedBigInteger('education_id')->nullable()->after('education');
            $table->unsignedBigInteger('occupation_id')->nullable()->after('occupation');

            $table->foreign('religion_id')->references('id')->on('religions')->onDelete('set null');
            $table->foreign('caste_id')->references('id')->on('castes')->onDelete('set null');
            $table->foreign('sub_caste_id')->references('id')->on('sub_castes')->onDelete('set null');
            $table->foreign('education_id')->references('id')->on('education')->onDelete('set null');
            $table->foreign('occupation_id')->references('id')->on('occupations')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropForeign(['religion_id']);
            $table->dropForeign(['caste_id']);
            $table->dropForeign(['sub_caste_id']);
            $table->dropForeign(['education_id']);
            $table->dropForeign(['occupation_id']);

            $table->dropColumn(['religion_id', 'caste_id', 'sub_caste_id', 'education_id', 'occupation_id']);
        });
    }
};
