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
        // This migration has been superseded by the fix migration
        // to properly handle data conversion from string to JSON
        // See: 2026_02_14_195331_fix_education_occupation_json_conversion.php
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration has been superseded by the fix migration
        // to properly handle data conversion from string to JSON
        // See: 2026_02_14_195331_fix_education_occupation_json_conversion.php
    }
};
