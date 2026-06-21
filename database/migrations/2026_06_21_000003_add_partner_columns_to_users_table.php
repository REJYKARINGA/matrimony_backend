<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('partner_office_id')->nullable()->after('reference_code');
            $table->foreign('partner_office_id')->references('id')->on('partner_offices')->onDelete('set null');
            $table->unsignedBigInteger('partner_agent_id')->nullable()->after('partner_office_id');
            $table->foreign('partner_agent_id')->references('id')->on('partner_agents')->onDelete('set null');
            $table->string('registered_by', 20)->default('self')->after('partner_agent_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['partner_office_id']);
            $table->dropForeign(['partner_agent_id']);
            $table->dropColumn(['partner_office_id', 'partner_agent_id', 'registered_by']);
        });
    }
};
