<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if district column exists before adding it
        if (!Schema::hasColumn('user_profiles', 'district')) {
            // Add district column with Kerala districts enum
            Schema::table('user_profiles', function (Blueprint $table) {
                $table->enum('district', [
                    'Thiruvananthapuram', 'Kollam', 'Pathanamthitta', 'Alappuzha',
                    'Kottayam', 'Idukki', 'Ernakulam', 'Thrissur', 'Palakkad',
                    'Malappuram', 'Kozhikode', 'Wayanad', 'Kannur', 'Kasaragod'
                ])->nullable()->after('city');
            });
        }

        // Update existing records that have NULL values to use the new defaults
        DB::table('user_profiles')
            ->whereNull('state')
            ->orWhere('state', '')
            ->update(['state' => 'Kerala']);

        DB::table('user_profiles')
            ->whereNull('country')
            ->orWhere('country', '')
            ->update(['country' => 'India']);

        // Since modifying column defaults can be database-specific and error-prone,
        // we'll just ensure the data is updated and rely on application logic for defaults going forward
        // The MODIFY COLUMN statements caused the original failure, so we'll skip them for now
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropColumn('district');
        });
    }
};
