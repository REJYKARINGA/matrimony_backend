<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('festivals', function (Blueprint $table) {
            $table->id();
            $table->string('celebration_name');
            $table->decimal('offer_discount', 10, 2)->nullable();
            $table->string('offer_discount_type', 20)->nullable();
            $table->string('calendar_type', 30); // gregorian_fixed, hijri, malayalam
            $table->string('hijri_event', 50)->nullable();
            $table->string('ml_event', 50)->nullable();
            $table->unsignedTinyInteger('fixed_month')->nullable();
            $table->unsignedTinyInteger('fixed_day')->nullable();
            $table->integer('start_offset_days')->default(0);
            $table->integer('end_offset_days')->default(0);
            $table->integer('reminder_days_before')->default(30);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('festival_occurrences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('festival_id')->constrained()->cascadeOnDelete();
            $table->integer('year');
            $table->dateTime('start_at');
            $table->dateTime('end_at');
            $table->string('resolved_from', 100)->nullable();
            $table->timestamps();

            $table->unique(['festival_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('festival_occurrences');
        Schema::dropIfExists('festivals');
    }
};
