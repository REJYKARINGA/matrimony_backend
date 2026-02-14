<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, backup and clean the data to ensure it's valid for JSON conversion
        $preferences = DB::table('preferences')->select('id', 'education', 'occupation')->get();
        
        foreach ($preferences as $preference) {
            // Process education field
            $educationValue = $preference->education;
            if (!is_null($educationValue) && trim($educationValue) !== '') {
                // Check if it's already valid JSON
                json_decode($educationValue);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    // If not valid JSON, wrap it in an array or convert appropriately
                    $educationValue = json_encode([$educationValue]);
                }
            } else {
                $educationValue = null;
            }
            
            // Process occupation field
            $occupationValue = $preference->occupation;
            if (!is_null($occupationValue) && trim($occupationValue) !== '') {
                // Check if it's already valid JSON
                json_decode($occupationValue);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    // If not valid JSON, wrap it in an array or convert appropriately
                    $occupationValue = json_encode([$occupationValue]);
                }
            } else {
                $occupationValue = null;
            }
            
            // Update the record with cleaned values
            DB::table('preferences')
                ->where('id', $preference->id)
                ->update([
                    'education' => $educationValue,
                    'occupation' => $occupationValue
                ]);
        }

        // Now safely change the column types to JSON
        Schema::table('preferences', function (Blueprint $table) {
            $table->json('education')->nullable()->change();
            $table->json('occupation')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('preferences', function (Blueprint $table) {
            $table->text('education')->nullable()->change();
            $table->text('occupation')->nullable()->change();
        });
    }
};
