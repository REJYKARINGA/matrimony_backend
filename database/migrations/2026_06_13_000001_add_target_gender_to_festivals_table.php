<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('festivals', function (Blueprint $table) {
            $table->string('target_gender', 10)->nullable()->after('reminder_days_before')
                ->comment('null=both, male, female');
        });
    }

    public function down(): void
    {
        Schema::table('festivals', function (Blueprint $table) {
            $table->dropColumn('target_gender');
        });
    }
};
