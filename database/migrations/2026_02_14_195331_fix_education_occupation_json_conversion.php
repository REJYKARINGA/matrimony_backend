<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, temporarily rename the columns to preserve data
        if (Schema::hasColumn('preferences', 'education')) {
            Schema::table('preferences', function (Blueprint $table) {
                $table->renameColumn('education', 'education_temp');
            });
        }
        
        if (Schema::hasColumn('preferences', 'occupation')) {
            Schema::table('preferences', function (Blueprint $table) {
                $table->renameColumn('occupation', 'occupation_temp');
            });
        }

        // Add new JSON columns with the original names
        Schema::table('preferences', function (Blueprint $table) {
            $table->json('education')->nullable();
            $table->json('occupation')->nullable();
        });

        // Copy data from temp columns to new JSON columns
        $preferences = DB::table('preferences')->select('id', 'education_temp', 'occupation_temp')->get();
        
        foreach ($preferences as $preference) {
            $educationData = null;
            $occupationData = null;
            
            // Process education data
            if (!empty($preference->education_temp)) {
                // Check if it's already valid JSON
                json_decode($preference->education_temp);
                if (json_last_error() === JSON_ERROR_NONE) {
                    // It's already valid JSON
                    $educationData = $preference->education_temp;
                } else {
                    // Convert to JSON array format
                    $educationData = json_encode([$preference->education_temp]);
                }
            }
            
            // Process occupation data
            if (!empty($preference->occupation_temp)) {
                // Check if it's already valid JSON
                json_decode($preference->occupation_temp);
                if (json_last_error() === JSON_ERROR_NONE) {
                    // It's already valid JSON
                    $occupationData = $preference->occupation_temp;
                } else {
                    // Convert to JSON array format
                    $occupationData = json_encode([$preference->occupation_temp]);
                }
            }
            
            // Update the record with JSON-formatted data
            DB::table('preferences')
                ->where('id', $preference->id)
                ->update([
                    'education' => $educationData,
                    'occupation' => $occupationData
                ]);
        }

        // Remove the temporary columns
        Schema::table('preferences', function (Blueprint $table) {
            $table->dropColumn(['education_temp', 'occupation_temp']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add back temporary columns
        Schema::table('preferences', function (Blueprint $table) {
            $table->string('education_temp')->nullable();
            $table->string('occupation_temp')->nullable();
        });

        // Copy data back from JSON columns to temp columns
        $preferences = DB::table('preferences')->select('id', 'education', 'occupation')->get();
        
        foreach ($preferences as $preference) {
            $educationData = null;
            $occupationData = null;
            
            if (!empty($preference->education)) {
                $decodedEducation = json_decode($preference->education, true);
                if (is_array($decodedEducation) && count($decodedEducation) > 0) {
                    $educationData = $decodedEducation[0]; // Take first element
                } else {
                    $educationData = $preference->education;
                }
            }
            
            if (!empty($preference->occupation)) {
                $decodedOccupation = json_decode($preference->occupation, true);
                if (is_array($decodedOccupation) && count($decodedOccupation) > 0) {
                    $occupationData = $decodedOccupation[0]; // Take first element
                } else {
                    $occupationData = $preference->occupation;
                }
            }
            
            DB::table('preferences')
                ->where('id', $preference->id)
                ->update([
                    'education_temp' => $educationData,
                    'occupation_temp' => $occupationData
                ]);
        }

        // Rename columns back to original names
        Schema::table('preferences', function (Blueprint $table) {
            $table->renameColumn('education', 'education_json');
            $table->renameColumn('occupation', 'occupation_json');
            $table->renameColumn('education_temp', 'education');
            $table->renameColumn('occupation_temp', 'occupation');
        });

        // Drop the JSON columns
        Schema::table('preferences', function (Blueprint $table) {
            $table->dropColumn(['education_json', 'occupation_json']);
        });
    }
};
