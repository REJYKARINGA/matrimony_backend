<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('follow_up_status', 30)->nullable()->after('status')
                ->comment('not_contacted, reached_out, payment_done, not_interested, follow_up_later, wrong_number, no_response');
            $table->text('follow_up_response')->nullable()->after('follow_up_status');
            $table->timestamp('follow_up_contacted_at')->nullable()->after('follow_up_response');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['follow_up_status', 'follow_up_response', 'follow_up_contacted_at']);
        });
    }
};
