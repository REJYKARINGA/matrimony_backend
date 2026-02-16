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
        Schema::table('users', function (Blueprint $table) {
            $table->index('status');
            $table->index('deleted_at');
            $table->index('role');
            $table->index(['status', 'deleted_at']);
            $table->index('last_login');
        });

        Schema::table('user_profiles', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('gender');
            $table->index('religion_id');
            $table->index('caste_id');
            $table->index('sub_caste_id');
            $table->index('education_id');
            $table->index('occupation_id');
            $table->index('marital_status');
            $table->index('is_active_verified');
            $table->index('date_of_birth');
            $table->index('height');
            $table->index('annual_income');
            $table->index('district');
            $table->index(['latitude', 'longitude']);
            $table->index(['is_active_verified', 'gender']);
            $table->index(['religion_id', 'caste_id']);
        });

        Schema::table('preferences', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('religion_id');
            $table->index('marital_status');
        });

        Schema::table('profile_photos', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('is_primary');
            $table->index(['user_id', 'is_primary']);
        });

        Schema::table('family_details', function (Blueprint $table) {
            $table->index('user_id');
        });

        Schema::table('interests_sent', function (Blueprint $table) {
            $table->index('sender_id');
            $table->index('receiver_id');
            $table->index('status');
            $table->index(['sender_id', 'status']);
            $table->index(['receiver_id', 'status']);
        });

        Schema::table('blocked_users', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('blocked_user_id');
        });

        Schema::table('contact_unlocks', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('unlocked_user_id');
            $table->index(['user_id', 'unlocked_user_id']);
        });

        Schema::table('profile_views', function (Blueprint $table) {
            $table->index('viewer_id');
            $table->index('viewed_profile_id');
            $table->index('viewed_at');
        });

        Schema::table('shortlisted_profiles', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('shortlisted_user_id');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('is_read');
            $table->index(['user_id', 'is_read']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['deleted_at']);
            $table->dropIndex(['role']);
            $table->dropIndex(['status', 'deleted_at']);
            $table->dropIndex(['last_login']);
        });

        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['gender']);
            $table->dropIndex(['religion_id']);
            $table->dropIndex(['caste_id']);
            $table->dropIndex(['sub_caste_id']);
            $table->dropIndex(['education_id']);
            $table->dropIndex(['occupation_id']);
            $table->dropIndex(['marital_status']);
            $table->dropIndex(['is_active_verified']);
            $table->dropIndex(['date_of_birth']);
            $table->dropIndex(['height']);
            $table->dropIndex(['annual_income']);
            $table->dropIndex(['district']);
            $table->dropIndex(['latitude', 'longitude']);
            $table->dropIndex(['is_active_verified', 'gender']);
            $table->dropIndex(['religion_id', 'caste_id']);
        });

        Schema::table('preferences', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['religion_id']);
            $table->dropIndex(['marital_status']);
        });

        Schema::table('profile_photos', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['is_primary']);
            $table->dropIndex(['user_id', 'is_primary']);
        });

        Schema::table('family_details', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
        });

        Schema::table('interests_sent', function (Blueprint $table) {
            $table->dropIndex(['sender_id']);
            $table->dropIndex(['receiver_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['sender_id', 'status']);
            $table->dropIndex(['receiver_id', 'status']);
        });

        Schema::table('blocked_users', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['blocked_user_id']);
        });

        Schema::table('contact_unlocks', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['unlocked_user_id']);
            $table->dropIndex(['user_id', 'unlocked_user_id']);
        });

        Schema::table('profile_views', function (Blueprint $table) {
            $table->dropIndex(['viewer_id']);
            $table->dropIndex(['viewed_profile_id']);
            $table->dropIndex(['viewed_at']);
        });

        Schema::table('shortlisted_profiles', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['shortlisted_user_id']);
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['is_read']);
            $table->dropIndex(['user_id', 'is_read']);
        });
    }
};
