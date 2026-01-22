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
        // Add district column with Kerala districts enum
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->enum('district', [
                'Thiruvananthapuram', 'Kollam', 'Pathanamthitta', 'Alappuzha',
                'Kottayam', 'Idukki', 'Ernakulam', 'Thrissur', 'Palakkad',
                'Malappuram', 'Kozhikode', 'Wayanad', 'Kannur', 'Kasaragod'
            ])->nullable()->after('city');
        });

        // Update existing records to have default values and change column definitions
        DB::statement("ALTER TABLE user_profiles MODIFY COLUMN state VARCHAR(255) DEFAULT 'Kerala'");
        DB::statement("ALTER TABLE user_profiles MODIFY COLUMN country VARCHAR(255) DEFAULT 'India'");

        // Update existing records that have NULL values to use the new defaults
        DB::table('user_profiles')
            ->whereNull('state')
            ->update(['state' => 'Kerala']);

        DB::table('user_profiles')
            ->whereNull('country')
            ->update(['country' => 'India']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropColumn('district');
            $table->string('state')->nullable()->change();
            $table->string('country')->nullable()->change();
        });
    }
};
