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
        Schema::table('family_details', function (Blueprint $table) {
            $table->integer('elder_sister')->default(0);
            $table->integer('elder_brother')->default(0);
            $table->integer('younger_sister')->default(0);
            $table->integer('younger_brother')->default(0);
            $table->enum('twin_type', ['identical', 'fraternal'])->nullable();
            $table->boolean('father_alive')->nullable();
            $table->boolean('mother_alive')->nullable();
            $table->string('guardian')->nullable();
            $table->boolean('show')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('family_details', function (Blueprint $table) {
            $table->dropColumn(['elder_sister', 'elder_brother', 'younger_sister', 'younger_brother', 'twin_type', 'father_alive', 'mother_alive', 'guardian', 'show']);
        });
    }
};
